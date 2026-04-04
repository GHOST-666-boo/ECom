<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 68: Admin Endpoints Require Admin Role
     * 
     * For any request to an admin endpoint (Filament /admin routes) from a user
     * without role = 'admin', the response should be HTTP 403 Forbidden.
     * 
     * **Validates: Requirements 8.1, 8.2**
     */
    public function test_property_68_admin_endpoints_require_admin_role(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a customer user (non-admin)
            $customer = User::factory()->create([
                'role' => 'customer',
                'email_verified_at' => now(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);
            
            // Test various admin endpoints
            $adminEndpoints = [
                '/admin',
                '/admin/categories',
                '/admin/categories/create',
            ];
            
            foreach ($adminEndpoints as $endpoint) {
                $response = $this->actingAs($customer)->get($endpoint);
                
                // Non-admin users should receive HTTP 403 Forbidden
                $this->assertEquals(403, $response->status(), 
                    "Customer user should not be able to access {$endpoint}");
            }
            
            // Now test with an admin user
            $admin = User::factory()->create([
                'role' => 'admin',
                'email_verified_at' => now(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);
            
            // Admin should be able to access admin endpoints
            $response = $this->actingAs($admin)->get('/admin');
            $this->assertEquals(200, $response->status(),
                "Admin user should be able to access /admin");
        }
    }
}
