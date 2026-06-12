<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates Indian GSTIN format.
 * Format: 2-digit state code + 10-digit PAN + 1-digit entity number + Z + 1-digit checksum
 * Example: 07AABCV1234D1Z5
 */
class ValidGstin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', strtoupper($value))) {
            $fail('The :attribute must be a valid 15-character GSTIN (e.g. 07AABCV1234D1Z5).');
        }
    }
}
