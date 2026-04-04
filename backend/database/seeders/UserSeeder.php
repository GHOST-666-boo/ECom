<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 2 admin users and 18 customer users.
     */
    public function run(): void
    {
        // Create 2 admin users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@artisankala.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone' => '9876543210',
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@artisankala.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone' => '9876543211',
        ]);

        // Create 18 customer users with realistic Indian names
        $customerNames = [
            'Priya Sharma',
            'Rahul Verma',
            'Anjali Patel',
            'Vikram Singh',
            'Sneha Reddy',
            'Arjun Kumar',
            'Kavita Desai',
            'Rohan Mehta',
            'Pooja Gupta',
            'Amit Joshi',
            'Neha Kapoor',
            'Sanjay Iyer',
            'Divya Nair',
            'Karan Malhotra',
            'Ritu Agarwal',
            'Manish Rao',
            'Swati Kulkarni',
            'Aditya Pandey',
        ];

        foreach ($customerNames as $index => $name) {
            $firstName = explode(' ', $name)[0];
            $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
            
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => fake()->boolean(90) ? now() : null, // 90% verified
                'phone' => '98765432' . str_pad($index + 12, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->command->info('Created 2 admin users and 18 customer users');
        $this->command->info('Admin credentials: admin@artisankala.com / password');
        $this->command->info('Customer credentials: [name]@example.com / password');
    }
}
