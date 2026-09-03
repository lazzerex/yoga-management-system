<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['invoice_id', 'recorded_by_user_id', 'amount', 'status', 'method', 'paid_at', 'reference', 'note', 'voided_at', 'voided_by_user_id', 'void_reason'])]
class Payment extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const METHODS = ['cash', 'transfer'];

    public const STATUSES = ['recorded', 'voided'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof')->singleFile();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function scopeRecorded(Builder $query): Builder
    {
        return $query->where('status', 'recorded');
    }
}
