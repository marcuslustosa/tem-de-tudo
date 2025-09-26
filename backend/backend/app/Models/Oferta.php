<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'title',
        'description',
        'points_required',
        'value',
        'category',
        'image',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resgates()
    {
        return $this->hasMany(Resgate::class);
    }
}
