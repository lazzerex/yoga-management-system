<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_profile_id' => StudentProfile::factory(),
            'class_session_id' => ClassSession::factory(),
            'status' => 'booked',
            'enrolled_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function waitlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waitlisted',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
