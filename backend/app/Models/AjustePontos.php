<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjustePontos extends Model
{
    protected $table = 'ajustes_pontos';

    protected $fillable = ['user_id', 'admin_id', 'pontos', 'motivo'];

    protected function casts(): array
    {
        return ['pontos' => 'integer'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
