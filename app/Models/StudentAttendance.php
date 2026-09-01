<?php

namespace App\Models;

use Database\Factories\StudentAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'status', 'notes', 'marked_by_user_id', 'marked_at'])]
class StudentAttendance extends Model
{
    /** @use HasFactory<StudentAttendanceFactory> */
    use HasFactory;

    public const STATUSES = ['present', 'late', 'absent'];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }
}
