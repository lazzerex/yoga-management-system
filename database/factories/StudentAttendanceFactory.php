<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\StudentAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'status' => 'present',
            'notes' => null,
            'marked_by_user_id' => null,
            'marked_at' => now(),
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'absent']);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'late']);
    }
}
