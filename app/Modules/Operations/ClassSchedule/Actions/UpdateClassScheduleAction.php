<?php

namespace App\Modules\Operations\ClassSchedule\Actions;

use App\Models\ClassSchedule;
use Carbon\Carbon;

class UpdateClassScheduleAction
{
    public function execute(ClassSchedule $classSchedule, array $validated): ClassSchedule
    {
        $classSchedule->update($validated);

        $endTime = Carbon::parse($classSchedule->start_time)
            ->addMinutes($classSchedule->duration_minutes)
            ->format('H:i:s');

        // Only future, not-manually-overridden, still-scheduled, not-yet-booked sessions follow the template.
        $classSchedule->classSessions()
            ->where('session_date', '>=', now()->toDateString())
            ->where('is_overridden', false)
            ->where('status', 'scheduled')
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

        return $classSchedule;
    }
}
