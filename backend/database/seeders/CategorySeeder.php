<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 10 categories with parent-child relationships.
     */
    public function run(): void
    {
        // Create parent categories
        $jewelry = Category::create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
            'image' => 'categories/jewelry.jpg',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $pottery = Category::create([
            'name' => 'Pottery & Ceramics',
            'slug' => 'pottery-ceramics',
            'image' => 'categories/pottery.jpg',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $textiles = Category::create([
            'name' => 'Textiles & Fabrics',
            'slug' => 'textiles-fabrics',
            'image' => 'categories/textiles.jpg',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $woodwork = Category::create([
            'name' => 'Woodwork',
            'slug' => 'woodwork',
            'image' => 'categories/woodwork.jpg',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $metalcraft = Category::create([
            'name' => 'Metal Craft',
            'slug' => 'metal-craft',
            'image' => 'categories/metalcraft.jpg',
            'parent_id' => null,
            'is_active' => true,
        ]);

        // Create child categories for Jewelry
        Category::create([
            'name' => 'Necklaces',
            'slug' => 'necklaces',
            'image' => 'categories/necklaces.jpg',
            'parent_id' => $jewelry->id,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Earrings',
            'slug' => 'earrings',
            'image' => 'categories/earrings.jpg',
            'parent_id' => $jewelry->id,
            'is_active' => true,
        ]);

        // Create child categories for Pottery
        Category::create([
            'name' => 'Decorative Pottery',
            'slug' => 'decorative-pottery',
            'image' => 'categories/decorative-pottery.jpg',
            'parent_id' => $pottery->id,
            'is_active' => true,
        ]);

        // Create child categories for Textiles
        Category::create([
            'name' => 'Handloom Sarees',
            'slug' => 'handloom-sarees',
            'image' => 'categories/handloom-sarees.jpg',
            'parent_id' => $textiles->id,
            'is_active' => true,
        ]);

        // Create child category for Woodwork
        Category::create([
            'name' => 'Carved Furniture',
            'slug' => 'carved-furniture',
            'image' => 'categories/carved-furniture.jpg',
            'parent_id' => $woodwork->id,
            'is_active' => true,
        ]);

        $this->command->info('Created 10 categories with parent-child relationships');
    }
}
