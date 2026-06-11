<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateEnquiry extends Model
{
    protected $fillable = [
        'company_name',
        'company_email',
        'contact_number',
        'categories',
        'message',
        'status',
    ];

    protected $casts = [
        'categories' => 'array',
    ];
}
