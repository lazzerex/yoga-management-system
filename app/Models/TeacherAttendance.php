<?php

namespace App\Models;

use Database\Factories\TeacherAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['class_session_id', 'coach_profile_id', 'checked_in_at', 'checked_out_at', 'notes'])]
class TeacherAttendance extends Model
{
    /** @use HasFactory<TeacherAttendanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function coachProfile(): BelongsTo
    {
        return $this->belongsTo(CoachProfile::class);
    }

    public function taughtMinutes(): int
    {
        return $this->checked_out_at
            ? (int) round($this->checked_in_at->diffInMinutes($this->checked_out_at))
            : 0;
    }
}
