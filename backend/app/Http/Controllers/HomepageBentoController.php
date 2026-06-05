<?php

namespace App\Http\Controllers;

use App\Models\HomepageBentoSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomepageBentoController extends Controller
{
    /**
     * Get all homepage bento slots.
     * Cache results for 1 hour, clearing on update.
     */
    public function index(): JsonResponse
    {
        try {
            $slots = Cache::remember('homepage_bento_slots', 3600, function () {
                return HomepageBentoSlot::with(['category', 'product'])
                    ->orderBy('slot_key', 'asc')
                    ->get()
                    ->toArray();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cache unavailable for bento slots', ['error' => $e->getMessage()]);
            // Fallback if cache is not available
            $slots = HomepageBentoSlot::with(['category', 'product'])
                ->orderBy('slot_key', 'asc')
                ->get()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Homepage bento slots retrieved successfully',
            'bento_slots' => $slots,
        ]);
    }
}
