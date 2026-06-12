<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Invoice;
use App\Models\CreditNote;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('r2');
        config([
            'invoice.seller_state' => 'Delhi',
            'invoice.seller_gstin' => '07AABCV1234D1Z5',
            'invoice.seller_name' => 'Vriddhi',
            'invoice.seller_address' => '123 Seller Lane, Delhi',
            'invoice.pdf_disk' => 'r2',
        ]);
    }

    public function test_invoice_is_automatically_generated_on_order_delivery_for_intrastate(): void
    {
        $category = Category::factory()->create([
            'hsn_code' => '6117',
            'gst_rate' => 12.00,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'hsn_code' => null, // Use category default
            'gst_rate' => null, // Use category default
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        // Delhi is intra-state since seller is in Delhi
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'address_snapshot' => [
                'name' => 'John Doe',
                'line1' => 'Street 1',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'pincode' => '110001',
            ],
            'total' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Confirming delivery triggers invoice auto-generation
        $order->status = 'delivered';
        $order->save();

        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'invoice_type' => 'B2C',
            'buyer_state' => 'Delhi',
            'subtotal' => 89.28,
            'cgst' => 5.36,
            'sgst' => 5.36,
            'igst' => 0.00,
            'total_amount' => 100.00,
        ]);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->pdf_path);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'hsn_code' => '6117',
            'gst_rate' => 12.00,
            'taxable_value' => 89.28,
            'cgst_amount' => 5.36,
            'sgst_amount' => 5.36,
            'igst_amount' => 0.00,
            'line_total' => 100.00,
        ]);

        Storage::disk('r2')->assertExists($invoice->pdf_path);
    }

    public function test_invoice_is_automatically_generated_for_interstate(): void
    {
        $category = Category::factory()->create([
            'hsn_code' => '6117',
            'gst_rate' => 18.00,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200.00,
            'hsn_code' => '7113', // Override category
            'gst_rate' => 18.00,
        ]);

        $customer = User::factory()->create();

        // Mumbai (Maharashtra) is inter-state
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'address_snapshot' => [
                'name' => 'Jane Smith',
                'line1' => 'Street 2',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ],
            'total' => 200.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 200.00,
        ]);

        $order->status = 'delivered';
        $order->save();

        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'buyer_state' => 'Maharashtra',
            'subtotal' => 169.49,
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => 30.51,
            'total_amount' => 200.00,
        ]);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'hsn_code' => '7113',
            'line_total' => 200.00,
        ]);
    }

    public function test_invoice_is_idempotent(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Manually generate first
        $service = app(InvoiceService::class);
        $invoice1 = $service->generateInvoice($order);

        // Transition status which fires observer
        $order->status = 'delivered';
        $order->save();

        // Count invoices for this order
        $count = Invoice::where('order_id', $order->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_admin_can_cancel_invoice_and_generate_credit_note(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $customer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        // Set up invoice
        $service = app(InvoiceService::class);
        $invoice = $service->generateInvoice($order);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/orders/{$order->id}/invoice/cancel", [
                'reason' => 'return',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('credit_notes', [
            'invoice_id' => $invoice->id,
            'order_id' => $order->id,
            'reason' => 'return',
            'total_amount' => $invoice->total_amount,
        ]);
    }
}
