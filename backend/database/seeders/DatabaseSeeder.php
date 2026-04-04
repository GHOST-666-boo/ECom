<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');
        
        // Seed in correct order to maintain referential integrity
        $this->call([
            UserSeeder::class,        // Creates 2 admins + 18 customers
            CategorySeeder::class,    // Creates 10 categories with parent-child relationships
            ProductSeeder::class,     // Creates 100 products across categories
            OrderSeeder::class,       // Creates 50 orders with various statuses
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
