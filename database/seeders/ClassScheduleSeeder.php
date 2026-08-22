<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::orderBy('name')->first();
        $coachProfile = CoachProfile::first();
        $classTypes = ClassType::orderBy('name')->take(2)->get();

        if (! $branch || ! $coachProfile || $classTypes->count() < 2) {
            return;
        }

        $room = Room::where('branch_id', $branch->id)->orderBy('name')->first();

        if (! $room) {
            return;
        }

        $schedules = [
            ['class_type_id' => $classTypes[0]->id, 'day_of_week' => 1, 'start_time' => '07:00', 'duration_minutes' => 60],
            ['class_type_id' => $classTypes[1]->id, 'day_of_week' => 3, 'start_time' => '18:00', 'duration_minutes' => 75],
        ];

        foreach ($schedules as $schedule) {
            ClassSchedule::updateOrCreate(
                [
                    'room_id' => $room->id,
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                ],
                [
                    'branch_id' => $branch->id,
                    'class_type_id' => $schedule['class_type_id'],
                    'coach_profile_id' => $coachProfile->id,
                    'duration_minutes' => $schedule['duration_minutes'],
                    'capacity' => min(15, $room->capacity),
                ]
            );
        }
    }
}
