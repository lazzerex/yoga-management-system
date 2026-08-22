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
        $branches = Branch::orderBy('name')->get();
        $coachProfile = CoachProfile::first();
        $classTypes = ClassType::orderBy('name')->get();

        if ($branches->isEmpty() || ! $coachProfile || $classTypes->count() < 2) {
            return;
        }

        // One coach, so every slot below is a distinct day+time — no double-booking
        // across branches even though they all share the same seeded coach.
        $slots = [
            ['day_of_week' => 1, 'start_time' => '07:00', 'duration_minutes' => 60],
            ['day_of_week' => 3, 'start_time' => '18:00', 'duration_minutes' => 75],
            ['day_of_week' => 2, 'start_time' => '07:00', 'duration_minutes' => 60],
            ['day_of_week' => 4, 'start_time' => '18:00', 'duration_minutes' => 75],
            ['day_of_week' => 3, 'start_time' => '08:00', 'duration_minutes' => 60],
            ['day_of_week' => 5, 'start_time' => '17:30', 'duration_minutes' => 60],
        ];

        // Schedule counts deliberately vary per branch so demo data isn't identical everywhere.
        $scheduleCountByBranch = [
            'Downtown Studio' => 2,
            'Riverside Center' => 1,
            'Uptown Loft' => 1,
            'Westside Branch' => 2,
        ];

        $slotIndex = 0;

        foreach ($branches as $branch) {
            $room = Room::where('branch_id', $branch->id)->orderBy('name')->first();

            if (! $room) {
                continue;
            }

            $scheduleCount = $scheduleCountByBranch[$branch->name] ?? 1;

            for ($i = 0; $i < $scheduleCount; $i++) {
                $slot = $slots[$slotIndex % count($slots)];
                $classType = $classTypes[$slotIndex % $classTypes->count()];
                $slotIndex++;

                ClassSchedule::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'day_of_week' => $slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                    ],
                    [
                        'branch_id' => $branch->id,
                        'class_type_id' => $classType->id,
                        'coach_profile_id' => $coachProfile->id,
                        'duration_minutes' => $slot['duration_minutes'],
                        'capacity' => min(15, $room->capacity),
                    ]
                );
            }
        }
    }
}
