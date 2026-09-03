<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'student_profile_id' => StudentProfile::factory(),
            'branch_id' => Branch::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'issued_at' => $issuedAt,
            'due_date' => (clone $issuedAt)->modify('+14 days'),
            'status' => 'unpaid',
            'total_amount' => fake()->numberBetween(5, 50) * 100000,
            'note' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'issued_at' => now()->subDays(45),
            'due_date' => now()->subDays(31),
        ]);
    }
}
