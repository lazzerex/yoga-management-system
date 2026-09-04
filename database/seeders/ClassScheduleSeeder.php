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
    /**
     * Every slot is a distinct day + time pair across the whole centre, so a coach
     * teaching at two branches can never be double-booked and the session generator
     * never has to skip one silently.
     */
    private const SLOTS_BY_BRANCH = [
        'Downtown Studio' => [
            ['day_of_week' => 1, 'start_time' => '06:30', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 1, 'start_time' => '18:00', 'duration_minutes' => 75, 'room' => 'Studio B'],
            ['day_of_week' => 3, 'start_time' => '07:00', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 4, 'start_time' => '08:00', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 5, 'start_time' => '12:15', 'duration_minutes' => 45, 'room' => 'Studio B'],
            ['day_of_week' => 6, 'start_time' => '09:00', 'duration_minutes' => 90, 'room' => 'Studio A'],
        ],
        'Westside Branch' => [
            ['day_of_week' => 2, 'start_time' => '07:30', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 4, 'start_time' => '18:30', 'duration_minutes' => 75, 'room' => 'Studio A'],
            ['day_of_week' => 5, 'start_time' => '19:00', 'duration_minutes' => 60, 'room' => 'Studio B'],
            ['day_of_week' => 6, 'start_time' => '10:30', 'duration_minutes' => 60, 'room' => 'Studio B'],
        ],
        'Riverside Center' => [
            ['day_of_week' => 1, 'start_time' => '19:00', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 2, 'start_time' => '19:30', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 5, 'start_time' => '06:30', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 0, 'start_time' => '09:30', 'duration_minutes' => 75, 'room' => 'Studio A'],
        ],
        'Uptown Loft' => [
            ['day_of_week' => 3, 'start_time' => '12:00', 'duration_minutes' => 45, 'room' => 'Studio A'],
            ['day_of_week' => 4, 'start_time' => '07:00', 'duration_minutes' => 60, 'room' => 'Studio A'],
            ['day_of_week' => 5, 'start_time' => '17:30', 'duration_minutes' => 75, 'room' => 'Studio A'],
            ['day_of_week' => 6, 'start_time' => '16:00', 'duration_minutes' => 60, 'room' => 'Studio A'],
        ],
    ];

    public function run(): void
    {
        $branches = Branch::orderBy('name')->get();
        $coachProfiles = CoachProfile::active()->orderBy('id')->get();
        $classTypes = ClassType::orderBy('name')->get();

        if ($branches->isEmpty() || $coachProfiles->isEmpty() || $classTypes->count() < 2) {
            return;
        }

        $index = 0;

        foreach ($branches as $branch) {
            foreach (self::SLOTS_BY_BRANCH[$branch->name] ?? [] as $slot) {
                $room = Room::where('branch_id', $branch->id)->where('name', $slot['room'])->first()
                    ?? Room::where('branch_id', $branch->id)->orderBy('name')->first();

                if (! $room) {
                    continue;
                }

                ClassSchedule::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'day_of_week' => $slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                    ],
                    [
                        'branch_id' => $branch->id,
                        'class_type_id' => $classTypes[$index % $classTypes->count()]->id,
                        'coach_profile_id' => $coachProfiles[$index % $coachProfiles->count()]->id,
                        'duration_minutes' => $slot['duration_minutes'],
                        'capacity' => min(16, $room->capacity),
                    ]
                );

                $index++;
            }
        }
    }
}
