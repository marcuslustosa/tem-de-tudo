<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cnpj',
        'address',
        'phone',
        'email',
        'photos',
        'services',
        'user_id',
    ];

    protected $casts = [
        'photos' => 'array',
        'services' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pontos()
    {
        return $this->hasMany(Ponto::class);
    }

    // public function ofertas()
    // {
    //     return $this->hasMany(Oferta::class);
    // }
}
