<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create customer user
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Create test order
        $this->order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_update_order_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order' => [
                        'id' => $this->order->id,
                        'status' => 'confirmed',
                    ],
                ],
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_non_admin_cannot_update_order_status(): void
    {
        $response = $this->actingAs($this->customer)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access denied. Admin role required.',
            ]);

        // Verify database was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'pending',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_order_status(): void
    {
        $response = $this->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(401);
    }

    public function test_pending_can_transition_to_confirmed(): void
    {
        $this->order->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $this->order->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_confirmed_can_transition_to_shipped(): void
    {
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'shipped',
        ]);
    }

    public function test_confirmed_can_transition_to_cancelled(): void
    {
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_shipped_can_transition_to_delivered(): void
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_pending_cannot_transition_to_shipped(): void
    {
        $this->order->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status transition',
                'errors' => [
                    'status' => ['Cannot transition from pending to shipped'],
                ],
            ]);

        // Verify database was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_cannot_transition_to_delivered(): void
    {
        $this->order->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status transition',
            ]);
    }

    public function test_confirmed_cannot_transition_to_delivered(): void
    {
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status transition',
            ]);
    }

    public function test_shipped_cannot_transition_to_confirmed(): void
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status transition',
            ]);
    }

    public function test_shipped_cannot_transition_to_cancelled(): void
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid status transition',
            ]);
    }

    public function test_delivered_cannot_transition_to_any_status(): void
    {
        $this->order->update(['status' => 'delivered']);

        $statuses = ['pending', 'confirmed', 'shipped', 'cancelled'];

        foreach ($statuses as $status) {
            $response = $this->actingAs($this->admin)
                ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                    'status' => $status,
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid status transition',
                ]);
        }

        // Verify database was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_cancelled_cannot_transition_to_any_status(): void
    {
        $this->order->update(['status' => 'cancelled']);

        $statuses = ['pending', 'confirmed', 'shipped', 'delivered'];

        foreach ($statuses as $status) {
            $response = $this->actingAs($this->admin)
                ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                    'status' => $status,
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid status transition',
                ]);
        }

        // Verify database was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_status_field_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_status_must_be_valid_value(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/orders/99999/status', [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    public function test_response_includes_updated_timestamp(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/orders/{$this->order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'status',
                        'updated_at',
                    ],
                ],
            ]);
    }
}
