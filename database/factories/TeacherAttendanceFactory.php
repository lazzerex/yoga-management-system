<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\TeacherAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAttendance>
 */
class TeacherAttendanceFactory extends Factory
{
    public function definition(): array
    {
        $session = ClassSession::factory()->create();

        return [
            'class_session_id' => $session->id,
            'coach_profile_id' => $session->coach_profile_id,
            'checked_in_at' => now()->subHours(2),
            'checked_out_at' => null,
            'notes' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now()->subHour(),
        ]);
    }
}
