<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'company_id',
        'type',
        'channel',
        'sent',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Tipos de notificação
     */
    const TYPE_REMINDER_3_DAYS = 'reminder_3_days';
    const TYPE_REMINDER_1_DAY = 'reminder_1_day';
    const TYPE_DUE_DATE = 'due_date';
    const TYPE_OVERDUE_3_DAYS = 'overdue_3_days';
    const TYPE_OVERDUE_7_DAYS = 'overdue_7_days';

    /**
     * Relacionamentos
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function company()
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }
}
