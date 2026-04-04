<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TestCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent categories
        $jewelry = Category::factory()->create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
        ]);

        $pottery = Category::factory()->create([
            'name' => 'Pottery',
            'slug' => 'pottery',
        ]);

        $textiles = Category::factory()->create([
            'name' => 'Textiles',
            'slug' => 'textiles',
        ]);

        // Create child categories
        $necklaces = Category::factory()->create([
            'name' => 'Necklaces',
            'slug' => 'necklaces',
            'parent_id' => $jewelry->id,
        ]);

        $earrings = Category::factory()->create([
            'name' => 'Earrings',
            'slug' => 'earrings',
            'parent_id' => $jewelry->id,
        ]);

        // Create products for each category
        Product::factory()->count(5)->create(['category_id' => $jewelry->id]);
        Product::factory()->count(3)->create(['category_id' => $pottery->id]);
        Product::factory()->count(4)->create(['category_id' => $textiles->id]);
        Product::factory()->count(2)->create(['category_id' => $necklaces->id]);
        Product::factory()->count(2)->create(['category_id' => $earrings->id]);

        $this->command->info('Created ' . Category::count() . ' categories and ' . Product::count() . ' products');
    }
}
