<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_profile_id', 'class_session_id', 'invoice_item_id', 'status', 'enrolled_at', 'cancelled_at'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory, HasReference;

    public const REFERENCE_PREFIX = 'BK';

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /** A place that spends a plan session: not cancelled by the member, and its class not cancelled by the centre. */
    public function scopeConsuming(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled')
            ->whereHas('classSession', fn (Builder $q) => $q->where('status', '!=', 'cancelled'));
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(StudentAttendance::class);
    }
}
