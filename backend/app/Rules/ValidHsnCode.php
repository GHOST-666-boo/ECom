<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates HSN Code format.
 * HSN codes must be 4–8 numeric digits only.
 * Examples: 6117 (knitted accessories), 71131910 (gold jewellery)
 */
class ValidHsnCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d{4,8}$/', $value)) {
            $fail('The :attribute must be a valid HSN code (4 to 8 digits, numeric only).');
        }
    }
}
