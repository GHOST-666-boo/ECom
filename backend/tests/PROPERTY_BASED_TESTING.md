# Property-Based Testing Setup

This document describes the property-based testing setup for the Artisan Kala e-commerce platform.

## Overview

Property-based testing validates universal properties across all inputs using randomized test data. This approach complements traditional unit tests by ensuring correctness across the entire input space rather than just specific examples.

## Framework

- **Testing Framework**: [Pest PHP](https://pestphp.com/) v4.4.3
- **Laravel Plugin**: pestphp/pest-plugin-laravel v4.1.0
- **Data Generation**: FakerPHP (included with Laravel)

## Configuration

### PHPUnit Configuration

The `phpunit.xml` file has been configured with:

```xml
<env name="PROPERTY_TEST_ITERATIONS" value="100"/>
```

This ensures each property test runs a minimum of 100 iterations for statistical confidence.

### Pest Configuration

The `tests/Pest.php` file configures:
- Test case binding to `Tests\TestCase`
- Database refresh for Feature tests
- Laravel-specific testing helpers

## Writing Property Tests

### Test Structure

Property tests should follow this structure:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: artisan-kala-ecommerce, Property 16: Product Slug Generation
it('generates URL-friendly slugs for any product name', function () {
    $iterations = (int) env('PROPERTY_TEST_ITERATIONS', 100);
    
    for ($i = 0; $i < $iterations; $i++) {
        $productName = fake()->words(rand(1, 5), true);
        
        $product = Product::factory()->create(['name' => $productName]);
        
        expect($product->slug)
            ->toMatch('/^[a-z0-9-]+$/')
            ->and($product->slug)->not->toContain(' ');
    }
});
```

### Naming Convention

Each property test must include a comment linking it to the design document:

```php
// Feature: artisan-kala-ecommerce, Property {number}: {property_text}
```

### Test Annotations

Use the `**Validates: Requirements X.Y**` format in test descriptions to link properties to requirements:

```php
/**
 * **Validates: Requirements 2.3**
 */
it('generates URL-friendly slugs for any product name', function () {
    // test implementation
});
```

## Running Tests

### Run All Tests

```bash
./vendor/bin/pest
```

### Run Specific Test File

```bash
./vendor/bin/pest tests/Feature/ProductTest.php
```

### Run Tests with Coverage

```bash
./vendor/bin/pest --coverage
```

### Run Tests in Parallel

```bash
./vendor/bin/pest --parallel
```

## Test Data Generation

Use Laravel's Faker instance via the `fake()` helper:

```php
// Generate random strings
$name = fake()->name();
$email = fake()->email();
$text = fake()->sentence();

// Generate random numbers
$price = fake()->randomFloat(2, 1, 1000);
$quantity = fake()->numberBetween(1, 100);

// Generate random dates
$date = fake()->dateTimeBetween('-1 year', 'now');
```

## Best Practices

1. **Minimum 100 Iterations**: Always use the `PROPERTY_TEST_ITERATIONS` environment variable
2. **Isolated Tests**: Each iteration should be independent and not rely on previous iterations
3. **Clear Properties**: Test one universal property per test function
4. **Meaningful Assertions**: Use descriptive expectations that clearly validate the property
5. **Database Refresh**: Use `RefreshDatabase` trait for tests that interact with the database
6. **Factory Usage**: Leverage Laravel factories for creating test data

## Property Test Categories

### Authentication Properties (1-14)
- Password hashing
- Token expiry
- Email verification
- OAuth flows

### Product Catalog Properties (15-28)
- Slug generation
- Image validation
- Filtering and pagination
- N+1 query prevention

### Shopping Cart Properties (29-36)
- Cart operations
- Quantity validation
- Stock checking

### Order Management Properties (37-67)
- Order placement
- Status transitions
- Price and address snapshots

### Payment Processing Properties (47-57)
- Webhook verification
- Payment status updates
- Idempotency

### Security Properties (80-82)
- Security headers
- HSTS enforcement
- Fingerprinting prevention

### Stock Management Properties (92-94)
- Atomic operations
- Pessimistic locking
- Stock validation

## Continuous Integration

Property tests are run automatically in the CI/CD pipeline:

```yaml
- name: Run Pest tests
  run: ./vendor/bin/pest
```

## Test Coverage Goals

- Overall code coverage: > 80%
- Critical paths (auth, orders, payments): > 95%
- Property tests: 100 iterations minimum per property

## Troubleshooting

### Tests Running Slowly

If property tests are slow, consider:
- Reducing iterations for development (use 10-20 locally)
- Using in-memory SQLite database (already configured)
- Running tests in parallel with `--parallel` flag

### Faker Data Issues

If Faker generates invalid data:
- Add validation constraints to your generators
- Use custom generators for domain-specific data
- Validate generated data before using it in tests

### Database Issues

If database tests fail:
- Ensure `RefreshDatabase` trait is used
- Check that migrations are up to date
- Verify SQLite is available for in-memory testing

## Resources

- [Pest PHP Documentation](https://pestphp.com/docs)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [FakerPHP Documentation](https://fakerphp.github.io/)
- [Property-Based Testing Concepts](https://hypothesis.works/articles/what-is-property-based-testing/)
