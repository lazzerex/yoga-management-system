<?php

namespace App\Models;

use Database\Factories\TuitionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['branch_id', 'name', 'type', 'price_amount', 'session_count', 'duration_days', 'description', 'is_active'])]
class TuitionPlan extends Model
{
    /** @use HasFactory<TuitionPlanFactory> */
    use HasFactory;

    public const TYPES = ['monthly', 'pack', 'course'];

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
