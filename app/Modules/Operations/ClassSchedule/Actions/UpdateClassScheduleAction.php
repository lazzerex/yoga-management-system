<?php

namespace App\Modules\Operations\ClassSchedule\Actions;

use App\Models\ClassSchedule;
use App\Models\LessonPlan;
use App\Modules\Operations\ClassSession\Actions\GenerateClassSessionsAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class UpdateClassScheduleAction
{
    public function __construct(private GenerateClassSessionsAction $generate) {}

    public function execute(ClassSchedule $classSchedule, array $validated): ClassSchedule
    {
        $weekdayChanged = (int) $classSchedule->day_of_week !== (int) $validated['day_of_week'];

        DB::transaction(function () use ($classSchedule, $validated, $weekdayChanged) {
            $classSchedule->update($validated);

            if ($weekdayChanged) {
                $this->openSessions($classSchedule)
                    ->whereDoesntHave('enrollments')
                    ->whereDoesntHave('teacherAttendances')
                    ->whereNotIn('id', LessonPlan::whereNotNull('class_session_id')->select('class_session_id'))
                    ->delete();
            }

            $endTime = Carbon::parse($classSchedule->start_time)
                ->addMinutes($classSchedule->duration_minutes)
                ->format('H:i:s');

            // Only future, not-manually-overridden, still-scheduled, not-yet-booked sessions follow the template.
            $this->openSessions($classSchedule)
                ->whereDoesntHave('enrollments', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->update([
                    'branch_id' => $classSchedule->branch_id,
                    'room_id' => $classSchedule->room_id,
                    'class_type_id' => $classSchedule->class_type_id,
                    'coach_profile_id' => $classSchedule->coach_profile_id,
                    'start_time' => $classSchedule->start_time,
                    'end_time' => $endTime,
                    'capacity' => $classSchedule->capacity,
                ]);

            if ($weekdayChanged && $classSchedule->is_active) {
                $this->generate->forSchedule($classSchedule);
            }
        });

        return $classSchedule;
    }

    private function openSessions(ClassSchedule $classSchedule): HasMany
    {
        return $classSchedule->classSessions()
            ->where('session_date', '>=', now()->toDateString())
            ->where('is_overridden', false)
            ->where('status', 'scheduled');
    }
}
