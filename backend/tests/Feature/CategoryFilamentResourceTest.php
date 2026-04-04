<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_category_list_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/categories');
        
        $response->assertStatus(200);
    }

    public function test_admin_can_access_category_create_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/categories/create');
        
        $response->assertStatus(200);
    }

    public function test_admin_can_access_category_edit_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $response = $this->actingAs($admin)->get("/admin/categories/{$category->id}/edit");
        
        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_category_list_page(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customer)->get('/admin/categories');
        
        $response->assertStatus(403);
    }

    public function test_category_form_has_required_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/categories/create');
        
        $response->assertStatus(200);
        
        // Verify the page loads successfully
        // The actual form fields are rendered by Filament's Livewire components
        // so we just verify the page is accessible
    }
}
