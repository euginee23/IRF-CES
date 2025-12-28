<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skillset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'years_experience',
        'certifications',
        'is_primary',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
