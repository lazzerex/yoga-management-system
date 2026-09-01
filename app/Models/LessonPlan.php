<?php

namespace App\Models;

use Database\Factories\LessonPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['coach_profile_id', 'branch_id', 'class_type_id', 'class_session_id', 'title', 'objective', 'asana_sequence', 'duration_minutes', 'level', 'status', 'submitted_at'])]
class LessonPlan extends Model
{
    /** @use HasFactory<LessonPlanFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'pending', 'approved', 'rejected'];

    public const EDITABLE_STATUSES = ['draft', 'rejected'];

    public const LEVELS = ['beginner', 'intermediate', 'advanced'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function coachProfile(): BelongsTo
    {
        return $this->belongsTo(CoachProfile::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function classType(): BelongsTo
    {
        return $this->belongsTo(ClassType::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LessonPlanReview::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }
}
