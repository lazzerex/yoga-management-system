<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    public function definition(): array
    {
        $room = Room::factory()->create();

        return [
            'branch_id' => $room->branch_id,
            'room_id' => $room->id,
            'class_type_id' => ClassType::factory(),
            'coach_profile_id' => CoachProfile::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => fake()->randomElement(['06:00', '08:00', '10:00', '17:30', '19:00']),
            'duration_minutes' => fake()->randomElement([45, 60, 75, 90]),
            'capacity' => fake()->numberBetween(1, $room->capacity),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
