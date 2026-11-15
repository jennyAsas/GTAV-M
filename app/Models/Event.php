<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'chapter_id', 'created_by', 'name', 'type', 'description', 'venue',
    'starts_at', 'ends_at', 'registration_cutoff', 'capacity', 'ticket_price',
])]
class Event extends Model
{
    public const TYPES = ['expo' => 'Expo', 'summit' => 'Summit', 'workshop' => 'Workshop'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_cutoff' => 'datetime',
            'capacity' => 'integer',
            'ticket_price' => 'decimal:2',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class);
    }

    public function gateStaff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_assignments')
            ->wherePivot('assignment', 'gate_staff');
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_assignments')
            ->wherePivot('assignment', 'speaker');
    }

    /** Seats held by pending or paid registrations; rejected ones free their seat. */
    public function seatsTaken(): int
    {
        return $this->registrations()->whereIn('payment_status', ['pending', 'paid'])->count();
    }

    public function seatsRemaining(): int
    {
        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFree(): bool
    {
        return (float) $this->ticket_price <= 0;
    }

    public function isRegistrationOpen(): bool
    {
        return now()->lt($this->registration_cutoff);
    }

    /** Proposed rule: scanning runs from the start day of the event until it ends. */
    public function isScanWindowOpen(): bool
    {
        return now()->between($this->starts_at->copy()->startOfDay(), $this->ends_at);
    }
}
