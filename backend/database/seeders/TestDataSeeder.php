<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test category
        $category = Category::create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
            'is_active' => true,
        ]);

        // Create test products
        Product::create([
            'category_id' => $category->id,
            'name' => 'Handmade Silver Necklace',
            'slug' => 'handmade-silver-necklace',
            'description' => 'Beautiful handcrafted silver necklace with intricate designs',
            'price' => 2500.00,
            'stock' => 10,
            'images' => [],
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Beaded Bracelet',
            'slug' => 'beaded-bracelet',
            'description' => 'Colorful beaded bracelet made with natural stones',
            'price' => 500.00,
            'stock' => 20,
            'images' => [],
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Copper Earrings',
            'slug' => 'copper-earrings',
            'description' => 'Elegant copper earrings with traditional patterns',
            'price' => 750.00,
            'stock' => 15,
            'images' => [],
            'is_active' => true,
        ]);

        // Create test user if not exists
        if (!User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'role' => 'customer',
            ]);
        }

        $this->command->info('Test data created successfully!');
        $this->command->info('Test user: test@example.com / password');
    }
}
