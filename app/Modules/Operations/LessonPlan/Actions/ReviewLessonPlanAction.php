<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\LessonPlan;
use App\Models\User;
use App\Notifications\LessonPlanReviewedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReviewLessonPlanAction
{
    public function execute(LessonPlan $lessonPlan, array $validated, User $reviewer): LessonPlan
    {
        if ($lessonPlan->status !== 'pending') {
            throw ValidationException::withMessages([
                'action' => __('flash.lessonPlanNotPending'),
            ]);
        }

        if ($lessonPlan->coachProfile->user_id === $reviewer->id) {
            throw ValidationException::withMessages([
                'action' => __('flash.lessonPlanSelfReview'),
            ]);
        }

        $reviewed = DB::transaction(function () use ($lessonPlan, $validated, $reviewer) {
            $lessonPlan->reviews()->create([
                'reviewer_user_id' => $reviewer->id,
                'action' => $validated['action'],
                'comment' => $validated['comment'] ?? null,
                'reviewed_at' => now(),
            ]);

            $lessonPlan->update(['status' => $validated['action']]);

            return $lessonPlan;
        });

        if ($owner = $reviewed->coachProfile->user) {
            Notification::send($owner, new LessonPlanReviewedNotification($reviewed, $reviewer));
        }

        return $reviewed;
    }
}
