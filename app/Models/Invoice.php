<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_profile_id', 'branch_id', 'invoice_number', 'issued_at', 'due_date', 'status', 'total_amount', 'note'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const STATUSES = ['unpaid', 'partial', 'paid', 'waived'];

    public const OPEN_STATUSES = ['unpaid', 'partial'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_date' => 'date',
            'total_amount' => 'integer',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Voided payments keep their row and their proof but leave every balance. */
    public function recordedPayments(): HasMany
    {
        return $this->payments()->recorded();
    }

    public function paidAmount(): int
    {
        if ($this->recorded_payments_sum_amount !== null) {
            return (int) $this->recorded_payments_sum_amount;
        }

        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->where('status', 'recorded')->sum('amount');
        }

        return (int) $this->recordedPayments()->sum('amount');
    }

    public function statusFromPayments(): string
    {
        if ($this->status === 'waived') {
            return 'waived';
        }

        $paid = (int) $this->recordedPayments()->sum('amount');

        return match (true) {
            $paid >= $this->total_amount => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };
    }

    public function balance(): int
    {
        return max(0, $this->total_amount - $this->paidAmount());
    }

    // Overdue is derived, never stored: a stored flag would need a scheduler to stay true.
    public function isOverdue(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true)
            && $this->due_date->isBefore(today());
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES)->whereDate('due_date', '<', today());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }
}
