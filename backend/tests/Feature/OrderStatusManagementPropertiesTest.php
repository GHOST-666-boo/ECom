<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusManagementPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 60: Valid Status Transitions
     * 
     * For any order status update, the transition should follow the allowed rules:
     * - pending → confirmed, cancelled
     * - confirmed → shipped, cancelled
     * - shipped → delivered
     * - delivered → (no transitions)
     * - cancelled → (no transitions)
     * 
     * **Validates: Requirements 7.4**
     */
    public function test_property_60_valid_status_transitions(): void
    {
        $iterations = 15;
        
        // Define valid transitions
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];
        
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($validTransitions as $currentStatus => $allowedStatuses) {
                // Create admin user
                $admin = User::factory()->create(['role' => 'admin']);
                
                // Create customer
                $customer = User::factory()->create([
                    'email_verified_at' => now(),
                ]);
                
                // Test each allowed transition
                foreach ($allowedStatuses as $newStatus) {
                    // Create a fresh order for each transition test
                    $order = Order::factory()->create([
                        'user_id' => $customer->id,
                        'status'  => $currentStatus,
                    ]);

                    // Build payload — tracking_number required when shipping
                    $payload = ['status' => $newStatus];
                    if ($newStatus === 'shipped') {
                        $payload['tracking_number'] = 'TRACK' . strtoupper(uniqid());
                        $payload['courier_name']    = 'BlueDart';
                    }
                    
                    $response = $this->actingAs($admin)
                        ->putJson("/api/v1/admin/orders/{$order->id}/status", $payload);
                    
                    $response->assertStatus(200);
                    $response->assertJson([
                        'success' => true,
                        'data' => [
                            'order' => [
                                'status' => $newStatus,
                            ],
                        ],
                    ]);
                }
                
                // Test invalid transitions (all statuses except allowed ones)
                $allStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
                $invalidStatuses = array_diff($allStatuses, $allowedStatuses, [$currentStatus]);
                
                foreach ($invalidStatuses as $invalidStatus) {
                    // Create a fresh order for each invalid transition test
                    $order = Order::factory()->create([
                        'user_id' => $customer->id,
                        'status' => $currentStatus,
                    ]);
                    
                    $response = $this->actingAs($admin)
                        ->putJson("/api/v1/admin/orders/{$order->id}/status", [
                            'status' => $invalidStatus,
                        ]);
                    
                    $response->assertStatus(422);
                    $response->assertJson([
                        'success' => false,
                        'message' => 'Invalid status transition',
                    ]);
                }
            }
        }
    }

    /**
     * Property 61: Customer Cancel Pending Orders
     * 
     * For any order with status 'pending', a customer cancellation request
     * should update the status to 'cancelled'.
     * 
     * **Validates: Requirements 7.5**
     */
    public function test_property_61_customer_cancel_pending_orders(): void
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            // Create order with pending status
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            
            // Create order items
            $quantity = rand(1, 5);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
            
            // Customer cancels the order
            $response = $this->actingAs($user)
                ->putJson("/api/v1/orders/{$order->id}/cancel");
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'status' => 'cancelled',
                    ],
                ],
            ]);
            
            // Verify order status in database
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => 'cancelled',
            ]);
        }
    }

    /**
     * Property 62: Stock Restored on Cancellation
     * 
     * For any order cancellation (customer-initiated or auto-cancelled),
     * the product stock should be incremented by the order_item quantities.
     * 
     * **Validates: Requirements 7.6, 6.9, 15.5**
     */
    public function test_property_62_stock_restored_on_cancellation(): void
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create multiple products with varying stock
            $products = [];
            $orderItems = [];
            $productCount = rand(2, 5);
            
            for ($j = 0; $j < $productCount; $j++) {
                $initialStock = rand(10, 50);
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => $initialStock,
                    'is_active' => true,
                ]);
                
                $quantity = rand(1, 5);
                $products[] = [
                    'product' => $product,
                    'initial_stock' => $initialStock,
                    'quantity' => $quantity,
                ];
            }
            
            // Create order with pending status
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            
            // Create order items
            foreach ($products as $productData) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $productData['product']->id,
                    'quantity' => $productData['quantity'],
                    'price' => $productData['product']->price,
                ]);
            }
            
            // Customer cancels the order
            $response = $this->actingAs($user)
                ->putJson("/api/v1/orders/{$order->id}/cancel");
            
            $response->assertStatus(200);
            
            // Verify stock is restored for all products
            foreach ($products as $productData) {
                $product = $productData['product']->fresh();
                $expectedStock = $productData['initial_stock'] + $productData['quantity'];
                
                $this->assertEquals(
                    $expectedStock,
                    $product->stock,
                    "Stock for product {$product->id} should be restored from {$productData['initial_stock']} to {$expectedStock}"
                );
            }
        }
    }

    /**
     * Property 63: Customer Cannot Cancel Non-Pending Orders
     * 
     * For any order with status 'confirmed', 'shipped', or 'delivered',
     * a customer cancellation request should fail with a validation error.
     * 
     * **Validates: Requirements 7.7**
     */
    public function test_property_63_customer_cannot_cancel_non_pending_orders(): void
    {
        $iterations = 20;
        $nonPendingStatuses = ['confirmed', 'shipped', 'delivered'];
        
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($nonPendingStatuses as $status) {
                $user = User::factory()->create([
                    'email_verified_at' => now(),
                ]);
                
                $category = Category::factory()->create([
                    'slug' => 'category-' . uniqid() . '-' . $i . '-' . $status,
                ]);
                
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                
                // Create order with non-pending status
                $order = Order::factory()->create([
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
                
                // Create order items
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 5),
                    'price' => $product->price,
                ]);
                
                // Customer attempts to cancel the order
                $response = $this->actingAs($user)
                    ->putJson("/api/v1/orders/{$order->id}/cancel");
                
                $response->assertStatus(422);
                $response->assertJson([
                    'success' => false,
                    'message' => 'Order cannot be cancelled',
                ]);
                
                // Verify error message mentions the status
                $errors = $response->json('errors.status');
                $this->assertNotNull($errors);
                $this->assertStringContainsString($status, $errors[0]);
                
                // Verify order status unchanged in database
                $this->assertDatabaseHas('orders', [
                    'id' => $order->id,
                    'status' => $status,
                ]);
            }
        }
    }
}
