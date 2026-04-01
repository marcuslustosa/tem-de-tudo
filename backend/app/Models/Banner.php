<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'link',
        'active',
        'position',
        'starts_at',
        'ends_at',
        'payload',
    ];

    protected $casts = [
        'active' => 'boolean',
        'position' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'payload' => 'array',
    ];
}

