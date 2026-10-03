<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_the_user_list_narrows_by_search_and_role(): void
    {
        User::factory()->create(['name' => 'Mai Tran', 'role' => 'coach']);
        User::factory()->create(['name' => 'Binh Nguyen', 'role' => 'member']);

        $this->assertSame(['Mai Tran'], $this->names('/cms/admin/users?search=Mai', 'users'));
        $this->assertNotContains('Binh Nguyen', $this->names('/cms/admin/users?role=coach', 'users'));
    }

    public function test_the_coach_directory_narrows_by_name_and_status(): void
    {
        $this->coachProfile('Mai Tran');
        $this->coachProfile('Binh Nguyen', false);

        $this->assertSame(['Mai Tran'], $this->userNames('/cms/operations/coaches?search=Mai'));
        $this->assertSame(['Mai Tran'], $this->userNames('/cms/operations/coaches?status=active'));
        $this->assertSame(['Binh Nguyen'], $this->userNames('/cms/operations/coaches?status=inactive'));
    }

    public function test_the_student_directory_narrows_by_name(): void
    {
        $this->studentProfile('Mai Tran');
        $this->studentProfile('Binh Nguyen');

        $this->assertSame(['Mai Tran'], $this->userNames('/cms/operations/students?search=Mai'));
    }

    public function test_the_invoice_board_narrows_by_status_and_search(): void
    {
        $branch = Branch::factory()->create();
        $paid = $this->invoice($branch, 'paid', $this->studentProfile('Mai Tran'));
        $this->invoice($branch, 'unpaid', $this->studentProfile('Binh Nguyen'));

        $props = $this->props('/cms/operations/tuition-fees?status=paid', $branch);
        $numbers = array_column($props['invoices']['data'], 'invoice_number');

        $this->assertSame([$paid->invoice_number], $numbers);
        $this->assertSame(
            ['Mai Tran'],
            array_column($this->props('/cms/operations/tuition-fees?search=Mai', $branch)['invoices']['data'], 'student_name'),
        );
    }

    public function test_the_lesson_plan_list_narrows_by_status_and_title(): void
    {
        // Both plans sit in the branch the viewer is looking at: the list is branch-scoped.
        $branch = Branch::factory()->create();
        $coach = $this->coachProfile('Mai Tran');
        $this->lessonPlan($coach, 'Morning Flow', 'draft', $branch);
        $this->lessonPlan($coach, 'Evening Yin', 'pending', $branch);

        $byStatus = $this->props('/cms/operations/lesson-planning?status=pending', $branch)['plans']['data'];
        $bySearch = $this->props('/cms/operations/lesson-planning?search=Morning', $branch)['plans']['data'];

        $this->assertSame(['Evening Yin'], array_column($byStatus, 'title'));
        $this->assertSame(['Morning Flow'], array_column($bySearch, 'title'));
    }

    public function test_the_session_list_narrows_by_coach_and_date_range(): void
    {
        $branch = Branch::factory()->create();
        $mine = $this->coachProfile('Mai Tran');
        $theirs = $this->coachProfile('Binh Nguyen');

        ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'coach_profile_id' => $mine->id,
            'session_date' => today()->addDay()->toDateString(),
        ]);
        ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'coach_profile_id' => $theirs->id,
            'session_date' => today()->addDays(20)->toDateString(),
        ]);

        $byCoach = $this->props("/cms/operations/academy?coach_profile_id={$mine->id}", $branch)['sessions']['data'];
        $this->assertSame(['Mai Tran'], array_column($byCoach, 'coach_name'));

        $to = today()->addDays(5)->toDateString();
        $byDate = $this->props('/cms/operations/academy?from='.today()->toDateString()."&to={$to}", $branch)['sessions']['data'];
        $this->assertCount(1, $byDate);
    }

    public function test_a_filter_value_that_is_not_a_date_is_ignored(): void
    {
        $branch = Branch::factory()->create();
        ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'coach_profile_id' => $this->coachProfile('Mai Tran')->id,
            'session_date' => today()->addDay()->toDateString(),
        ]);

        $props = $this->props('/cms/operations/academy?from=not-a-date&status=nonsense', $branch);

        $this->assertCount(1, $props['sessions']['data']);
        $this->assertSame('', $props['filters']['from']);
    }

    public function test_the_tuition_plan_list_narrows_by_type(): void
    {
        TuitionPlan::factory()->create(['name' => 'Monthly Unlimited', 'type' => 'monthly']);
        TuitionPlan::factory()->pack(10)->create(['name' => '10-Class Pack']);

        $this->assertSame(['Monthly Unlimited'], array_column($this->props('/cms/operations/tuition-fees/plans?type=monthly')['plans']['data'], 'name'));
    }

    public function test_the_centre_page_narrows_every_tab_with_one_search(): void
    {
        Branch::factory()->create(['name' => 'Uptown Loft']);
        Branch::factory()->create(['name' => 'Riverside Center']);
        ClassType::factory()->create(['name' => 'Uptown Special']);
        ClassType::factory()->create(['name' => 'Yin Yoga']);

        $props = $this->props('/cms/operations/yoga-center?search=Uptown');

        $this->assertSame(['Uptown Loft'], array_column($props['branches']['data'], 'name'));
        $this->assertSame(['Uptown Special'], array_column($props['classTypes']['data'], 'name'));
    }

    private function props(string $url, ?Branch $branch = null): array
    {
        $request = $this->actingAs(User::factory()->create(['role' => 'admin']));

        if ($branch) {
            $request = $request->withUnencryptedCookie('branch_id', $branch->id);
        }

        return $request->get($url)->viewData('page')['props'];
    }

    /** @return string[] */
    private function names(string $url, string $key): array
    {
        return array_column($this->props($url)[$key]['data'], 'name');
    }

    /** @return string[] */
    private function userNames(string $url): array
    {
        return array_column($this->props($url)['profiles']['data'], 'user_name');
    }

    private function coachProfile(string $name, bool $active = true): CoachProfile
    {
        return CoachProfile::factory()->create([
            'user_id' => User::factory()->create(['name' => $name, 'role' => 'coach'])->id,
            'is_active' => $active,
        ]);
    }

    private function studentProfile(string $name): StudentProfile
    {
        return StudentProfile::factory()->create([
            'user_id' => User::factory()->create(['name' => $name, 'role' => 'member'])->id,
        ]);
    }

    private function invoice(Branch $branch, string $status, StudentProfile $student): Invoice
    {
        return Invoice::factory()->create([
            'branch_id' => $branch->id,
            'student_profile_id' => $student->id,
            'status' => $status,
        ]);
    }

    private function lessonPlan(CoachProfile $coach, string $title, string $status, Branch $branch): LessonPlan
    {
        return LessonPlan::factory()->create([
            'coach_profile_id' => $coach->id,
            'branch_id' => $branch->id,
            'title' => $title,
            'status' => $status,
        ]);
    }
}
