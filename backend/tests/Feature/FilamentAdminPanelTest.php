<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $response = $this->get('/admin');
        
        // Should redirect to login
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        
        $response->assertStatus(200);
    }

    public function test_customer_user_cannot_access_admin_panel(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/admin');
        
        // Should return 403 Forbidden
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');
        
        // Should redirect to login
        $response->assertRedirect('/admin/login');
    }
}
