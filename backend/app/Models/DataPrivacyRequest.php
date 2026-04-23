<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPrivacyRequest extends Model
{
    use HasFactory;

    public const TYPE_EXPORT = 'export';
    public const TYPE_DELETE_ACCOUNT = 'delete_account';
    public const TYPE_CONSENT_UPDATE = 'consent_update';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'request_type',
        'status',
        'reason',
        'file_path',
        'payload',
        'requested_ip',
        'requested_user_agent',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

