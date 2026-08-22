<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    public function definition(): array
    {
        // Snapshot from a real schedule by default, so branch_id/room_id/coach_profile_id
        // stay internally consistent the way GenerateClassSessionsAction produces them.
        $schedule = ClassSchedule::factory()->create();

        return [
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $schedule->class_type_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => fake()->dateTimeBetween('now', '+8 weeks')->format('Y-m-d'),
            'start_time' => $schedule->start_time,
            'end_time' => '19:00',
            'capacity' => $schedule->capacity,
            'status' => 'scheduled',
            'is_overridden' => false,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'is_overridden' => true,
        ]);
    }
}
