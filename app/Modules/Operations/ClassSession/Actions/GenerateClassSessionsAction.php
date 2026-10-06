<?php

namespace App\Modules\Operations\ClassSession\Actions;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Carbon\Carbon;

class GenerateClassSessionsAction
{
    private const WEEKS_AHEAD = 8;

    public function execute(int $weeksBack = 0): int
    {
        $created = 0;

        foreach (ClassSchedule::active()->get() as $schedule) {
            $created += $this->forSchedule($schedule, $weeksBack);
        }

        return $created;
    }

    public function forSchedule(ClassSchedule $schedule, int $weeksBack = 0): int
    {
        $created = 0;
        $lastDate = now()->copy()->addWeeks(self::WEEKS_AHEAD)->toDateString();
        $cursor = now()->copy()->subWeeks($weeksBack)->startOfDay();

        while ($cursor->toDateString() < $lastDate) {
            if ($cursor->dayOfWeek === (int) $schedule->day_of_week) {
                $created += $this->generateForDate($schedule, $cursor->toDateString());
            }

            $cursor->addDay();
        }

        return $created;
    }

    private function generateForDate(ClassSchedule $schedule, string $date): int
    {
        $alreadyExists = ClassSession::where('class_schedule_id', $schedule->id)
            ->where('session_date', $date)
            ->exists();

        if ($alreadyExists) {
            return 0;
        }

        $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = Carbon::parse($schedule->start_time)->addMinutes($schedule->duration_minutes)->format('H:i:s');

        $conflict = ClassSession::where('status', 'scheduled')
            ->where('session_date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->where(fn ($q) => $q->where('room_id', $schedule->room_id)->orWhere('coach_profile_id', $schedule->coach_profile_id))
            ->exists();

        if ($conflict) {
            return 0;
        }

        ClassSession::create([
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $schedule->class_type_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => $date,
            'start_time' => $schedule->start_time,
            'end_time' => $endTime,
            'capacity' => $schedule->capacity,
            'status' => $date < now()->toDateString() ? 'done' : 'scheduled',
        ]);

        return 1;
    }
}
