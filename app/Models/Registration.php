<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'event_id', 'user_id', 'ticket_code', 'payment_status', 'paid_at',
    'verified_by', 'registered_at',
])]
class Registration extends Model
{
    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    /** D3: a registration is "verified" when its payment is paid (free events are paid on registration). */
    public function isVerified(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }
}
