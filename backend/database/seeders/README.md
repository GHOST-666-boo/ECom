# Database Seeders

This directory contains seeders for populating the Artisan Kala e-commerce database with test data for development and testing purposes.

## Available Seeders

### 1. UserSeeder
Creates 20 users total:
- **2 Admin Users**:
  - admin@artisankala.com / password
  - superadmin@artisankala.com / password
- **18 Customer Users**: 
  - Format: [firstname.lastname]@example.com / password
  - Examples: priya.sharma@example.com, rahul.verma@example.com, etc.
  - 90% have verified emails, 10% unverified

### 2. CategorySeeder
Creates 10 categories with parent-child relationships:
- **5 Parent Categories**:
  - Jewelry
  - Pottery & Ceramics
  - Textiles & Fabrics
  - Woodwork
  - Metal Craft
- **5 Child Categories**:
  - Necklaces (under Jewelry)
  - Earrings (under Jewelry)
  - Decorative Pottery (under Pottery & Ceramics)
  - Handloom Sarees (under Textiles & Fabrics)
  - Carved Furniture (under Woodwork)

### 3. ProductSeeder
Creates 100 products distributed across categories:
- Realistic Indian handicraft product names and descriptions
- Price range: ₹100 - ₹12,000
- Stock distribution:
  - 5% out of stock (stock = 0)
  - 10% low stock (stock = 1-9)
  - 85% normal stock (stock = 10-100)
- 1-5 images per product
- 95% active, 5% inactive products

### 4. OrderSeeder
Creates 50 orders with various statuses:
- **Status Distribution**:
  - Pending: 5 orders (10%)
  - Confirmed: 15 orders (30%)
  - Shipped: 12 orders (24%)
  - Delivered: 15 orders (30%)
  - Cancelled: 3 orders (6%)
- Payment methods: Mix of COD and Razorpay
- 1-5 products per order
- Realistic Indian addresses with major cities
- Order dates vary based on status (pending orders within 48 hours, delivered orders up to 60 days old)

## Running Seeders

### Seed All Data
```bash
php artisan db:seed
```

### Seed Specific Seeder
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=OrderSeeder
```

### Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

## Data Summary

After running all seeders, you will have:
- 20 users (2 admins + 18 customers)
- 10 categories (5 parent + 5 child)
- 100 products across all categories
- 50 orders with ~165 order items
- Realistic test data for development and testing

## Notes

- All passwords are set to `password` for easy testing
- Product images are placeholder paths (actual images not included)
- Order dates are backdated to simulate realistic order history
- Stock levels include some low-stock and out-of-stock items for testing alerts
- Some users have unverified emails for testing email verification flow
