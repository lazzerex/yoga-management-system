<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['invoice_id', 'tuition_plan_id', 'description', 'quantity', 'unit_price', 'line_total', 'valid_from', 'valid_until', 'sessions_granted'])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tuitionPlan(): BelongsTo
    {
        return $this->belongsTo(TuitionPlan::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** A line without a plan is a one-off charge and grants nothing, however it is paid. */
    public function scopeGranting(Builder $query): Builder
    {
        return $query->whereNotNull('tuition_plan_id')
            ->whereHas('invoice', fn (Builder $q) => $q->whereIn('status', Invoice::GRANTING_STATUSES));
    }

    /** A plan line reads in the viewer's language; a one-off charge keeps the text it was billed with. */
    public function label(): string
    {
        return $this->tuitionPlan?->localizedName() ?? $this->description;
    }

    public function isUnlimited(): bool
    {
        return $this->sessions_granted === null;
    }

    /** A plan sold without a duration never expires. */
    public function coversDate(string $date): bool
    {
        if ($this->valid_from !== null && $this->valid_from->toDateString() > $date) {
            return false;
        }

        return $this->valid_until === null || $this->valid_until->toDateString() >= $date;
    }

    // Derived, never stored, so cancelling a booking returns the credit with no refund step.
    public function consumedCount(): int
    {
        if ($this->consumed_count !== null) {
            return (int) $this->consumed_count;
        }

        return $this->enrollments()->consuming()->count();
    }

    public function sessionsRemaining(): ?int
    {
        return $this->isUnlimited() ? null : max(0, $this->sessions_granted - $this->consumedCount());
    }
}
