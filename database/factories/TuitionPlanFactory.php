<?php

namespace Database\Factories;

use App\Models\TuitionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TuitionPlan>
 */
class TuitionPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => null,
            'name' => fake()->words(2, true),
            'type' => 'monthly',
            'price_amount' => fake()->numberBetween(5, 50) * 100000,
            'session_count' => null,
            'duration_days' => 30,
            'description' => fake()->sentence(6),
            'is_active' => true,
        ];
    }

    public function pack(int $sessions = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pack',
            'session_count' => $sessions,
            'duration_days' => 90,
        ]);
    }
}
