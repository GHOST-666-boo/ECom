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

class OrderHistoryPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 58: Order History Filtered by User
     * 
     * For any customer's order history request, all returned orders should
     * belong to that customer (user_id matches).
     * 
     * **Validates: Requirements 7.1**
     */
    public function test_property_58_order_history_filtered_by_user(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create two users
            $user1 = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            $user2 = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create orders for user1
            $user1OrderCount = rand(1, 3);
            for ($j = 0; $j < $user1OrderCount; $j++) {
                $address = Address::factory()->create(['user_id' => $user1->id]);
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                
                $order = Order::factory()->create([
                    'user_id' => $user1->id,
                    'address_snapshot' => [
                        'name' => $address->name,
                        'line1' => $address->line1,
                        'line2' => $address->line2,
                        'city' => $address->city,
                        'state' => $address->state,
                        'pincode' => $address->pincode,
                    ],
                ]);
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }
            
            // Create orders for user2
            $user2OrderCount = rand(1, 3);
            for ($j = 0; $j < $user2OrderCount; $j++) {
                $address = Address::factory()->create(['user_id' => $user2->id]);
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                
                $order = Order::factory()->create([
                    'user_id' => $user2->id,
                    'address_snapshot' => [
                        'name' => $address->name,
                        'line1' => $address->line1,
                        'line2' => $address->line2,
                        'city' => $address->city,
                        'state' => $address->state,
                        'pincode' => $address->pincode,
                    ],
                ]);
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }
            
            // Request order history for user1
            $response = $this->actingAs($user1)
                ->getJson('/api/v1/orders');
            
            $response->assertStatus(200);
            
            $orders = $response->json('orders');
            
            // Verify all returned orders belong to user1
            $this->assertCount($user1OrderCount, $orders);
            foreach ($orders as $order) {
                $this->assertEquals($user1->id, $order['user_id'], "Order {$order['id']} does not belong to user {$user1->id}");
            }
            
            // Request order history for user2
            $response2 = $this->actingAs($user2)
                ->getJson('/api/v1/orders');
            
            $response2->assertStatus(200);
            
            $orders2 = $response2->json('orders');
            
            // Verify all returned orders belong to user2
            $this->assertCount($user2OrderCount, $orders2);
            foreach ($orders2 as $order) {
                $this->assertEquals($user2->id, $order['user_id'], "Order {$order['id']} does not belong to user {$user2->id}");
            }
        }
    }

    /**
     * Property 59: Order Detail Completeness
     * 
     * For any order detail request, the response should include order_number,
     * status, total, and all order_items with product information.
     * 
     * **Validates: Requirements 7.2**
     */
    public function test_property_59_order_detail_completeness(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create order with multiple items
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            $itemCount = rand(1, 5);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }
            
            // Request order detail
            $response = $this->actingAs($user)
                ->getJson("/api/v1/orders/{$order->id}");
            
            $response->assertStatus(200);
            
            $orderData = $response->json('order');
            
            // Verify order_number is present
            $this->assertArrayHasKey('order_number', $orderData);
            $this->assertNotNull($orderData['order_number']);
            
            // Verify status is present
            $this->assertArrayHasKey('status', $orderData);
            $this->assertNotNull($orderData['status']);
            
            // Verify total is present
            $this->assertArrayHasKey('total', $orderData);
            $this->assertNotNull($orderData['total']);
            
            // Verify items are present
            $this->assertArrayHasKey('items', $orderData);
            $this->assertIsArray($orderData['items']);
            $this->assertCount($itemCount, $orderData['items']);
            
            // Verify each item has product information
            foreach ($orderData['items'] as $item) {
                $this->assertArrayHasKey('product', $item);
                $this->assertNotNull($item['product']);
                $this->assertArrayHasKey('id', $item['product']);
                $this->assertArrayHasKey('name', $item['product']);
            }
        }
    }

    /**
     * Property 65: Order Detail Includes Product Information
     * 
     * For any order detail request, each order_item should include product
     * information (name, images) from the products table.
     * 
     * **Validates: Requirements 7.9**
     */
    public function test_property_65_order_detail_includes_product_information(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create order with items
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            $itemCount = rand(1, 5);
            $productIds = [];
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                
                $productIds[$product->id] = [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'images' => $product->images,
                ];
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }
            
            // Request order detail
            $response = $this->actingAs($user)
                ->getJson("/api/v1/orders/{$order->id}");
            
            $response->assertStatus(200);
            
            $orderData = $response->json('order');
            
            // Verify each item includes product information
            foreach ($orderData['items'] as $item) {
                $this->assertArrayHasKey('product', $item);
                $this->assertNotNull($item['product']);
                
                $productInfo = $item['product'];
                $productId = $productInfo['id'];
                
                // Verify product information matches the actual product
                $this->assertArrayHasKey($productId, $productIds);
                $this->assertEquals($productIds[$productId]['name'], $productInfo['name']);
                $this->assertEquals($productIds[$productId]['slug'], $productInfo['slug']);
                $this->assertEquals($productIds[$productId]['images'], $productInfo['images']);
            }
        }
    }

    /**
     * Property 66: Price Snapshot Used in Order Details
     * 
     * For any order detail request, the prices displayed should come from the
     * order_items.price field (snapshot), not the current product price.
     * 
     * **Validates: Requirements 7.10**
     */
    public function test_property_66_price_snapshot_used_in_order_details(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create order with items
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            $itemCount = rand(1, 5);
            $orderItemPrices = [];
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                    'price' => rand(100, 1000) / 10, // Random price
                ]);
                
                // Store original price as snapshot
                $snapshotPrice = $product->price;
                
                $orderItem = OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $snapshotPrice,
                ]);
                
                $orderItemPrices[$orderItem->id] = $snapshotPrice;
                
                // Change product price after order creation
                $product->price = rand(100, 1000) / 10;
                $product->save();
            }
            
            // Request order detail
            $response = $this->actingAs($user)
                ->getJson("/api/v1/orders/{$order->id}");
            
            $response->assertStatus(200);
            
            $orderData = $response->json('order');
            
            // Verify each item uses price snapshot, not current product price
            foreach ($orderData['items'] as $item) {
                $itemId = $item['id'];
                $displayedPrice = (float) $item['price'];
                $snapshotPrice = (float) $orderItemPrices[$itemId];
                
                // Verify displayed price matches snapshot
                $this->assertEquals(
                    $snapshotPrice,
                    $displayedPrice,
                    "Order item {$itemId} should use snapshot price {$snapshotPrice}, not current product price"
                );
                
                // Verify displayed price does NOT match current product price (if changed)
                $currentProductPrice = (float) Product::find($item['product_id'])->price;
                if ($currentProductPrice !== $snapshotPrice) {
                    $this->assertNotEquals(
                        $currentProductPrice,
                        $displayedPrice,
                        "Order item {$itemId} should not use current product price {$currentProductPrice}"
                    );
                }
            }
        }
    }

    /**
     * Property 67: Address Snapshot Used in Order Details
     * 
     * For any order detail request, the delivery address displayed should come
     * from the order.address_snapshot field, not the current state of the
     * customer's saved addresses.
     * 
     * **Validates: Requirements 7.11**
     */
    public function test_property_67_address_snapshot_used_in_order_details(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Store original address data
            $originalAddressData = [
                'name' => $address->name,
                'line1' => $address->line1,
                'line2' => $address->line2,
                'city' => $address->city,
                'state' => $address->state,
                'pincode' => $address->pincode,
            ];
            
            // Create order with address snapshot
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'address_snapshot' => $originalAddressData,
            ]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
            ]);
            
            // Modify the address after order creation
            $address->name = 'Modified Name ' . uniqid();
            $address->line1 = 'Modified Line1 ' . uniqid();
            $address->city = 'Modified City ' . uniqid();
            $address->state = 'Modified State ' . uniqid();
            $address->pincode = '999999';
            $address->save();
            
            // Request order detail
            $response = $this->actingAs($user)
                ->getJson("/api/v1/orders/{$order->id}");
            
            $response->assertStatus(200);
            
            $orderData = $response->json('order');
            $addressSnapshot = $orderData['address_snapshot'];
            
            // Verify address snapshot matches original address, not modified address
            $this->assertEquals($originalAddressData['name'], $addressSnapshot['name']);
            $this->assertEquals($originalAddressData['line1'], $addressSnapshot['line1']);
            $this->assertEquals($originalAddressData['line2'], $addressSnapshot['line2']);
            $this->assertEquals($originalAddressData['city'], $addressSnapshot['city']);
            $this->assertEquals($originalAddressData['state'], $addressSnapshot['state']);
            $this->assertEquals($originalAddressData['pincode'], $addressSnapshot['pincode']);
            
            // Verify address snapshot does NOT match modified address
            $this->assertNotEquals($address->name, $addressSnapshot['name']);
            $this->assertNotEquals($address->line1, $addressSnapshot['line1']);
            $this->assertNotEquals($address->city, $addressSnapshot['city']);
            $this->assertNotEquals($address->state, $addressSnapshot['state']);
            $this->assertNotEquals($address->pincode, $addressSnapshot['pincode']);
        }
    }
}
