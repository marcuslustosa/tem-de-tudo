<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupomModel extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'user_id',
        'codigo',
        'descricao',
        'status',
        'validade',
    ];

    public $timestamps = true;
}
