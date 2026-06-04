<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Get list of user addresses.
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $addresses = Address::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Addresses retrieved successfully',
            'data' => [
                'addresses' => $addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'name' => $address->name,
                        'line1' => $address->line1,
                        'line2' => $address->line2,
                        'city' => $address->city,
                        'state' => $address->state,
                        'pincode' => $address->pincode,
                        'is_default' => $address->is_default,
                        'created_at' => $address->created_at,
                        'updated_at' => $address->updated_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Create a new address.
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'is_default' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // If this address is being set as default, unmark any existing default
        if (isset($validated['is_default']) && $validated['is_default']) {
            Address::where('user_id', $user->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        // Create the address
        $address = Address::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully',
            'data' => [
                'address' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                    'is_default' => $address->is_default,
                    'created_at' => $address->created_at,
                    'updated_at' => $address->updated_at,
                ],
            ],
        ], 201);
    }

    /**
     * Update an existing address.
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);

        $user = $request->user();

        // Find address and verify it belongs to user
        $address = Address::where('user_id', $user->id)->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        // Update the address
        $address->update([
            'name' => $validated['name'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => [
                'address' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                    'is_default' => $address->is_default,
                    'created_at' => $address->created_at,
                    'updated_at' => $address->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Delete an address (does not affect existing orders).
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        // Find address and verify it belongs to user
        $address = Address::where('user_id', $user->id)->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        // Delete the address
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Mark an address as default (unmark previous default).
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDefault(Request $request, int $id)
    {
        $user = $request->user();

        // Find address and verify it belongs to user
        $address = Address::where('user_id', $user->id)->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        // Use transaction to ensure atomicity
        try {
            DB::transaction(function () use ($user, $address) {
                // Unmark any existing default address
                Address::where('user_id', $user->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                // Mark this address as default
                $address->update(['is_default' => true]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to set default address', [
                'user_id' => $user->id,
                'address_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update default address. Please try again.',
            ], 500);
        }

        // Refresh the address to get updated data
        $address->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Default address updated successfully',
            'data' => [
                'address' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                    'is_default' => $address->is_default,
                    'created_at' => $address->created_at,
                    'updated_at' => $address->updated_at,
                ],
            ],
        ]);
    }
}
