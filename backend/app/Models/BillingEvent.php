<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'invoice_id',
        'event_type',
        'level',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function subscription()
    {
        return $this->belongsTo(CompanySubscription::class, 'subscription_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

