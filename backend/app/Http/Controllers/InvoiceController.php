<?php

namespace App\Http\Controllers;

use App\Http\Resources\CreditNoteResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    /**
     * POST /api/v1/orders/{order}/invoice
     * Manually trigger invoice generation for a delivered order.
     */
    public function generate(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Only the order owner or admin can generate invoice
        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Invoice only valid for delivered orders
        if ($order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice can only be generated for delivered orders.',
            ], 422);
        }

        try {
            $invoice = $this->invoiceService->generateInvoice($order);

            return response()->json([
                'success' => true,
                'message' => $order->invoice?->wasRecentlyCreated === false
                    ? 'Invoice already exists. Returning existing invoice.'
                    : 'Invoice generated successfully.',
                'invoice' => new InvoiceResource($invoice->load('items', 'order')),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Manual invoice generation failed', [
                'order_id' => $order->id,
                'user_id'  => $user->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/orders/{order}/invoice
     * Fetch invoice JSON for a specific order.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $invoice = $order->invoice?->load('items', 'order');

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice found for this order.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invoice' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * GET /api/v1/orders/{order}/invoice/pdf
     * Download invoice PDF via signed R2 URL redirect.
     */
    public function downloadPDF(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $invoice = $order->invoice;

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice found for this order.',
            ], 404);
        }

        try {
            $url = $this->invoiceService->generateDownloadUrl($invoice);
            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('Invoice PDF download failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PDF temporarily unavailable. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/orders/{order}/invoice/cancel
     * Cancel invoice and generate credit note.
     */
    public function cancelInvoice(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Only admin can cancel invoices
        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $invoice = $order->invoice;

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'No invoice found for this order.'], 404);
        }

        if ($invoice->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already cancelled.',
            ], 422);
        }

        $request->validate([
            'reason' => 'nullable|in:return,cancellation,discount',
        ]);

        try {
            $creditNote = $this->invoiceService->generateCreditNote(
                $invoice,
                $request->input('reason', 'cancellation')
            );

            return response()->json([
                'success'     => true,
                'message'     => 'Invoice cancelled and credit note issued.',
                'credit_note' => new CreditNoteResource($creditNote->load('invoice', 'order')),
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice cancellation failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to cancel invoice.'], 500);
        }
    }

    /**
     * GET /api/v1/seller/invoices
     * Paginated list of the authenticated user's invoices.
     * Filters: status, date_from, date_to, financial_year
     */
    public function sellerIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'status'           => 'nullable|in:generated,sent,cancelled',
            'date_from'        => 'nullable|date',
            'date_to'          => 'nullable|date|after_or_equal:date_from',
            'financial_year'   => 'nullable|string|regex:/^\d{4}-\d{2}$/',  // e.g. 2025-26
        ]);

        $query = Invoice::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->with(['items', 'order'])
            ->latest('invoice_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('financial_year')) {
            $query->where('invoice_number', 'like', "VRD-INV-{$request->financial_year}-%");
        }

        $invoices = $query->paginate(15);

        return response()->json([
            'success'  => true,
            'invoices' => InvoiceResource::collection($invoices),
            'meta'     => [
                'total'        => $invoices->total(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/invoices
     * All invoices — admin only.
     * Filters: status, date_from, date_to, financial_year, buyer_state
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required.'], 403);
        }

        $request->validate([
            'status'         => 'nullable|in:generated,sent,cancelled',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'financial_year' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'buyer_state'    => 'nullable|string|max:100',
            'invoice_type'   => 'nullable|in:B2C,B2B',
        ]);

        $query = Invoice::with(['items', 'order.user'])->latest('invoice_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('financial_year')) {
            $query->where('invoice_number', 'like', "VRD-INV-{$request->financial_year}-%");
        }
        if ($request->filled('buyer_state')) {
            $query->where('buyer_state', 'like', '%' . $request->buyer_state . '%');
        }
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        $invoices = $query->paginate(25);

        return response()->json([
            'success'  => true,
            'invoices' => InvoiceResource::collection($invoices),
            'meta'     => [
                'total'        => $invoices->total(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
            ],
        ]);
    }
}
