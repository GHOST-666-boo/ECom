<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 100 products distributed across categories with realistic data.
     */
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please run CategorySeeder first.');
            return;
        }

        // Product templates for different categories
        $jewelryProducts = [
            ['name' => 'Silver Filigree Necklace', 'price' => 2500, 'description' => 'Exquisite handcrafted silver necklace with intricate filigree work'],
            ['name' => 'Kundan Choker Set', 'price' => 4500, 'description' => 'Traditional Kundan choker with matching earrings'],
            ['name' => 'Temple Jewelry Necklace', 'price' => 3200, 'description' => 'South Indian temple jewelry with antique gold finish'],
            ['name' => 'Oxidized Silver Earrings', 'price' => 850, 'description' => 'Bohemian style oxidized silver jhumka earrings'],
            ['name' => 'Pearl Drop Earrings', 'price' => 1200, 'description' => 'Elegant freshwater pearl drop earrings'],
            ['name' => 'Meenakari Bangles Set', 'price' => 1800, 'description' => 'Colorful Meenakari work bangles set of 4'],
            ['name' => 'Tribal Silver Bracelet', 'price' => 950, 'description' => 'Handmade tribal design silver bracelet'],
            ['name' => 'Gold Plated Pendant', 'price' => 650, 'description' => 'Delicate gold plated pendant with chain'],
        ];

        $potteryProducts = [
            ['name' => 'Blue Pottery Vase', 'price' => 1500, 'description' => 'Jaipur blue pottery decorative vase with floral motifs'],
            ['name' => 'Terracotta Planter Set', 'price' => 800, 'description' => 'Set of 3 handmade terracotta planters'],
            ['name' => 'Ceramic Tea Set', 'price' => 2200, 'description' => 'Hand-painted ceramic tea set with 6 cups'],
            ['name' => 'Clay Diya Set', 'price' => 350, 'description' => 'Traditional clay diyas for festivals, set of 12'],
            ['name' => 'Decorative Wall Plate', 'price' => 1100, 'description' => 'Hand-painted ceramic wall hanging plate'],
        ];

        $textileProducts = [
            ['name' => 'Banarasi Silk Saree', 'price' => 8500, 'description' => 'Pure Banarasi silk saree with golden zari work'],
            ['name' => 'Kantha Embroidered Stole', 'price' => 1200, 'description' => 'Bengal Kantha work silk stole'],
            ['name' => 'Block Print Cotton Dupatta', 'price' => 650, 'description' => 'Rajasthani block print cotton dupatta'],
            ['name' => 'Handloom Cotton Saree', 'price' => 2500, 'description' => 'Handwoven cotton saree with temple border'],
            ['name' => 'Ikat Silk Fabric', 'price' => 1800, 'description' => 'Pochampally Ikat silk fabric per meter'],
        ];

        $woodworkProducts = [
            ['name' => 'Carved Wooden Box', 'price' => 950, 'description' => 'Sheesham wood carved jewelry box'],
            ['name' => 'Sandalwood Figurine', 'price' => 3500, 'description' => 'Hand-carved sandalwood Ganesha figurine'],
            ['name' => 'Rosewood Side Table', 'price' => 12000, 'description' => 'Handcrafted rosewood side table with brass inlay'],
            ['name' => 'Wooden Wall Art', 'price' => 2800, 'description' => 'Intricate wooden wall hanging with traditional motifs'],
        ];

        $metalcraftProducts = [
            ['name' => 'Brass Diya Stand', 'price' => 1200, 'description' => 'Traditional brass diya stand with 5 diyas'],
            ['name' => 'Copper Water Bottle', 'price' => 850, 'description' => 'Handmade copper water bottle with health benefits'],
            ['name' => 'Bronze Statue', 'price' => 4500, 'description' => 'Dhokra art bronze dancing lady statue'],
            ['name' => 'Brass Wall Hanging', 'price' => 1600, 'description' => 'Decorative brass wall hanging with peacock design'],
        ];

        $allProducts = array_merge(
            $jewelryProducts,
            $potteryProducts,
            $textileProducts,
            $woodworkProducts,
            $metalcraftProducts
        );

        $productsCreated = 0;

        // Create products distributed across categories
        foreach ($categories as $category) {
            $productsPerCategory = match($category->name) {
                'Jewelry' => 20,
                'Necklaces' => 12,
                'Earrings' => 12,
                'Pottery & Ceramics' => 10,
                'Decorative Pottery' => 8,
                'Textiles & Fabrics' => 15,
                'Handloom Sarees' => 10,
                'Woodwork' => 8,
                'Carved Furniture' => 3,
                'Metal Craft' => 2,
                default => 0,
            };

            for ($i = 0; $i < $productsPerCategory; $i++) {
                // Select a random product template
                $template = $allProducts[array_rand($allProducts)];
                
                // Generate unique product name
                $productName = $template['name'] . ' ' . fake()->randomElement(['Classic', 'Premium', 'Deluxe', 'Traditional', 'Modern', 'Vintage', '']);
                $productName = trim($productName);

                // Vary the price slightly
                $basePrice = $template['price'];
                $price = $basePrice + fake()->numberBetween(-200, 500);
                $price = max(100, $price); // Minimum price 100

                // Generate stock with some low-stock and out-of-stock items
                $stockRandom = fake()->numberBetween(1, 100);
                $stock = match(true) {
                    $stockRandom <= 5 => 0, // 5% out of stock
                    $stockRandom <= 15 => fake()->numberBetween(1, 9), // 10% low stock
                    default => fake()->numberBetween(10, 100), // 85% normal stock
                };

                // Generate images (1-5 images per product)
                $imageCount = fake()->numberBetween(1, 5);
                $images = [];
                for ($j = 0; $j < $imageCount; $j++) {
                    $images[] = "products/{$category->slug}-" . fake()->uuid() . ".jpg";
                }

                Product::create([
                    'category_id' => $category->id,
                    'name' => $productName,
                    'slug' => \Illuminate\Support\Str::slug($productName) . '-' . fake()->unique()->numberBetween(1000, 9999),
                    'description' => $template['description'] . '. ' . fake()->sentence(10),
                    'price' => $price,
                    'stock' => $stock,
                    'images' => $images,
                    'is_active' => fake()->boolean(95), // 95% active, 5% inactive
                ]);

                $productsCreated++;

                if ($productsCreated >= 100) {
                    break 2;
                }
            }
        }

        $this->command->info("Created {$productsCreated} products across categories");
    }
}
