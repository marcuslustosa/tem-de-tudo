<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoModel extends Model
{
    protected $table = 'historicos';

    protected $fillable = [
        'user_id',
        'acao',
        'data',
        'detalhes',
    ];

    public $timestamps = false;
}
