<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonPlan>
 */
class LessonPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coach_profile_id' => CoachProfile::factory(),
            'branch_id' => Branch::factory(),
            'class_type_id' => ClassType::factory(),
            'class_session_id' => null,
            'title' => fake()->sentence(3),
            'objective' => fake()->sentence(10),
            'asana_sequence' => implode("\n", fake()->words(8)),
            'duration_minutes' => fake()->randomElement([45, 60, 75, 90]),
            'level' => fake()->randomElement(LessonPlan::LEVELS),
            'status' => 'draft',
            'submitted_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now()->subDays(3),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => now()->subDays(2),
        ]);
    }
}
