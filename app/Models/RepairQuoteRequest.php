<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairQuoteRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'manufacturer',
        'model',
        'issue_description',
        'images',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
    ];
}
