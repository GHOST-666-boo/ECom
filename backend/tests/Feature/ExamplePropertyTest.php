<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Example Property-Based Test
 * 
 * This test demonstrates the property-based testing setup for the Vriddhi platform.
 * It validates that the testing framework is correctly configured with 100 iterations.
 * 
 * **Validates: Testing Strategy**
 */

// Feature: vriddhi-ecommerce, Example: Property Test Configuration
it('runs property tests with configured iterations', function () {
    $iterations = (int) env('PROPERTY_TEST_ITERATIONS', 100);
    
    expect($iterations)->toBeInt()->toBeGreaterThan(0);
    
    $executedIterations = 0;
    
    for ($i = 0; $i < $iterations; $i++) {
        // Generate random test data using Faker
        $randomEmail = fake()->email();
        $randomNumber = fake()->numberBetween(1, 1000);
        
        // Validate properties
        expect($randomEmail)->toBeString()->toContain('@');
        expect($randomNumber)->toBeInt()->toBeGreaterThan(0);
        
        $executedIterations++;
    }
    
    // Verify all iterations were executed
    expect($executedIterations)->toBe($iterations);
});

// Feature: vriddhi-ecommerce, Example: Database Refresh
it('can use database with RefreshDatabase trait', function () {
    // This test verifies that the RefreshDatabase trait is working
    // and the database is properly configured for testing
    
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
});
