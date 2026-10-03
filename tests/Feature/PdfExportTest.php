<?php

namespace Tests\Feature;

use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    private function assertIsPdf($response): void
    {
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_admin_downloads_an_invoice_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $this->assertIsPdf($this->actingAs($admin)->get("/cms/operations/tuition-fees/{$invoice->id}/pdf"));
    }

    public function test_a_member_downloads_their_own_invoice(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $profile = StudentProfile::factory()->create(['user_id' => $member->id]);
        $invoice = Invoice::factory()->create(['student_profile_id' => $profile->id]);

        // The member holds no tuition permission at all; ownership is what lets this through.
        $this->assertFalse($member->can('operations.tuition.view'));
        $this->assertIsPdf($this->actingAs($member)->get("/cms/operations/tuition-fees/{$invoice->id}/pdf"));
    }

    public function test_a_member_cannot_download_another_students_invoice(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);
        $other = Invoice::factory()->create();

        $this->actingAs($member)
            ->get("/cms/operations/tuition-fees/{$other->id}/pdf")
            ->assertForbidden();
    }

    public function test_a_coach_cannot_download_an_invoice(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $invoice = Invoice::factory()->create();

        $this->actingAs($coach)
            ->get("/cms/operations/tuition-fees/{$invoice->id}/pdf")
            ->assertForbidden();
    }

    public function test_an_admin_downloads_the_monthly_attendance_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertIsPdf($this->actingAs($admin)->get('/cms/operations/attendance/reports/pdf?month=2026-09'));
    }

    public function test_a_member_cannot_download_the_attendance_report(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get('/cms/operations/attendance/reports/pdf')
            ->assertForbidden();
    }

    public function test_a_coach_downloads_their_own_lesson_plan(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        $plan = LessonPlan::factory()->create([
            'coach_profile_id' => $profile->id,
            'class_type_id' => ClassType::factory()->create()->id,
        ]);

        $this->assertIsPdf($this->actingAs($coach)->get("/cms/operations/lesson-planning/{$plan->id}/pdf"));
    }

    public function test_a_coach_cannot_download_another_coachs_lesson_plan(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $plan = LessonPlan::factory()->create();

        $this->actingAs($coach)
            ->get("/cms/operations/lesson-planning/{$plan->id}/pdf")
            ->assertForbidden();
    }
}
