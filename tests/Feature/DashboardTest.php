<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_an_admin_gets_the_widgets_their_permissions_allow(): void
    {
        $widgets = $this->widgets($this->admin());

        $this->assertSame(
            ['money', 'revenue', 'todaySessions', 'timeline', 'occupancy', 'pendingPlans', 'newStudents'],
            array_keys($widgets),
        );
    }

    public function test_an_admin_without_the_tuition_permission_loses_the_money_widgets(): void
    {
        $admin = $this->admin();

        // Revoked on the role, since that is where the permission is granted.
        Role::findByName('admin')->revokePermissionTo('operations.tuition.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $widgets = $this->widgets($admin->fresh());

        $this->assertArrayNotHasKey('money', $widgets);
        $this->assertArrayNotHasKey('revenue', $widgets);
        $this->assertArrayHasKey('todaySessions', $widgets);
    }

    public function test_the_money_widget_quotes_the_same_figures_as_the_invoice_board(): void
    {
        $admin = $this->admin();
        $branch = Branch::factory()->create();
        $invoice = $this->invoice($branch, 1000000);
        $this->payment($invoice, 400000);

        $dashboard = $this->widgets($admin, $branch)['money'];
        $board = $this->props($admin, '/cms/operations/tuition-fees', $branch)['stats'];

        $this->assertSame($board['collected'], $dashboard['collected']);
        $this->assertSame($board['outstanding'], $dashboard['outstanding']);
        $this->assertSame(400000, $dashboard['collected']);
        $this->assertSame(600000, $dashboard['outstanding']);
    }

    public function test_the_money_widget_follows_the_branch_the_viewer_is_looking_at(): void
    {
        $admin = $this->admin();
        $uptown = Branch::factory()->create(['name' => 'Uptown']);
        $downtown = Branch::factory()->create(['name' => 'Downtown']);
        $this->payment($this->invoice($uptown, 1000000), 250000);

        $this->assertSame(250000, $this->widgets($admin, $uptown)['money']['collected']);
        $this->assertSame(0, $this->widgets($admin, $downtown)['money']['collected']);
    }

    public function test_a_coach_sees_only_their_own_sessions(): void
    {
        $branch = Branch::factory()->create();
        $mine = $this->coach();
        $theirs = CoachProfile::factory()->create(['user_id' => User::factory()->create(['role' => 'coach'])->id]);

        $this->classSession($branch, $mine->coachProfile);
        $this->classSession($branch, $theirs);

        $widgets = $this->widgets($mine);

        $this->assertSame(1, $widgets['todaySessions']['sessions']);
        $this->assertCount(1, $widgets['weekTimeline']);
        $this->assertArrayNotHasKey('money', $widgets);
    }

    public function test_a_member_sees_their_own_bookings_and_balance_only(): void
    {
        $branch = Branch::factory()->create();
        $member = $this->member();
        $stranger = StudentProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
        ]);

        $session = $this->classSession($branch, $this->coach()->coachProfile);
        Enrollment::factory()->create([
            'student_profile_id' => $member->studentProfile->id,
            'class_session_id' => $session->id,
            'status' => 'booked',
        ]);
        $this->invoice($branch, 800000, $stranger);
        $this->invoice($branch, 500000, $member->studentProfile);

        $widgets = $this->widgets($member);

        $this->assertSame(1, $widgets['upcoming']['count']);
        $this->assertSame(500000, $widgets['membership']['outstanding']);
        $this->assertArrayNotHasKey('money', $widgets);
        $this->assertArrayNotHasKey('timeline', $widgets);
    }

    public function test_an_admin_is_offered_every_analytics_view(): void
    {
        $props = $this->props($this->admin(), '/cms/dashboard');

        $this->assertSame(
            ['overview', 'dashboard', 'classes', 'people', 'attendance', 'financials'],
            array_column($props['tabs'], 'viewKey'),
        );
    }

    public function test_an_admin_lands_on_the_centre_overview_and_a_member_on_their_homepage(): void
    {
        $this->assertSame('overview', $this->props($this->admin(), '/cms/dashboard')['view']);
        $this->assertSame('dashboard', $this->props($this->member(), '/cms/dashboard')['view']);
    }

    public function test_the_overview_reads_every_branch_and_its_own_filter_narrows_it(): void
    {
        $uptown = Branch::factory()->create(['name' => 'Uptown']);
        $downtown = Branch::factory()->create(['name' => 'Downtown']);
        $this->payment($this->invoice($uptown, 1000000), 250000);
        $this->payment($this->invoice($downtown, 1000000), 400000);

        // The header switcher points at one branch; the overview still reports both.
        $all = $this->props($this->admin(), '/cms/dashboard?view=overview', $uptown)['analytics'];
        $this->assertSame(650000, $all['money']['collectedThisMonth']);
        $this->assertCount(2, $all['money']['series']);

        $narrowed = $this->props($this->admin(), "/cms/dashboard?view=overview&branch_id={$downtown->id}", $uptown)['analytics'];
        $this->assertSame(400000, $narrowed['money']['collectedThisMonth']);
        $this->assertSame(['Downtown'], array_column($narrowed['money']['series'], 'branch'));
    }

    public function test_the_overview_month_window_is_whitelisted(): void
    {
        $props = $this->props($this->admin(), '/cms/dashboard?view=overview&months=999');

        $this->assertSame(6, $props['filters']['months']);
        $this->assertCount(6, $props['analytics']['money']['months']);
    }

    public function test_an_admin_without_the_tuition_permission_sees_an_overview_without_money(): void
    {
        $admin = $this->admin();

        Role::findByName('admin')->revokePermissionTo('operations.tuition.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $analytics = $this->props($admin->fresh(), '/cms/dashboard?view=overview')['analytics'];

        $this->assertArrayNotHasKey('money', $analytics);
        $this->assertArrayHasKey('activity', $analytics);
    }

    public function test_a_view_the_viewer_may_not_open_falls_back_to_their_first_tab(): void
    {
        $admin = $this->admin();

        Role::findByName('admin')->revokePermissionTo('operations.tuition.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $props = $this->props($admin->fresh(), '/cms/dashboard?view=financials');

        $this->assertNotContains('financials', array_column($props['tabs'], 'viewKey'));
        $this->assertSame('overview', $props['view']);
    }

    public function test_a_coach_is_offered_their_own_view_and_not_the_admin_ones(): void
    {
        $props = $this->props($this->coach(), '/cms/dashboard');

        $this->assertSame(['dashboard', 'my-teaching'], array_column($props['tabs'], 'viewKey'));
    }

    public function test_a_member_cannot_reach_the_financial_view_by_url(): void
    {
        $props = $this->props($this->member(), '/cms/dashboard?view=financials');

        $this->assertSame('dashboard', $props['view']);
        $this->assertNull($props['analytics']);
    }

    public function test_the_classes_view_names_the_rooms_it_ranks(): void
    {
        $branch = Branch::factory()->create();
        $session = $this->classSession($branch, $this->coach()->coachProfile);
        $room = $session->room()->with('branch:id,name')->firstOrFail();

        $analytics = $this->props($this->admin(), '/cms/dashboard?view=classes', $branch)['analytics'];

        // The room's branch label only resolves if branch_id survived the column selection.
        $this->assertCount(1, $analytics['topRooms']);
        $this->assertSame($room->name.' · '.$room->branch->name, $analytics['topRooms'][0]['name']);
    }

    public function test_the_financial_view_reports_the_revenue_it_collected(): void
    {
        $branch = Branch::factory()->create();
        $this->payment($this->invoice($branch, 1000000), 250000);

        $props = $this->props($this->admin(), '/cms/dashboard?view=financials', $branch);
        $revenue = collect($props['analytics']['revenue']);

        $this->assertSame('financials', $props['view']);
        $this->assertSame(250000, $revenue->firstWhere('month', now()->format('Y-m'))['amount']);
        $this->assertSame(750000, collect($props['analytics']['ageing'])->sum('amount'));
    }

    private function widgets(User $user, ?Branch $branch = null): array
    {
        // An admin now lands on the centre overview, so the widget board is asked for by name.
        return $this->props($user, '/cms/dashboard?view=dashboard', $branch)['widgets'];
    }

    private function props(User $user, string $url, ?Branch $branch = null): array
    {
        $request = $this->actingAs($user);

        if ($branch) {
            $request = $request->withUnencryptedCookie('branch_id', $branch->id);
        }

        return $request->get($url)->viewData('page')['props'];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function coach(): User
    {
        $user = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function member(): User
    {
        $user = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function classSession(Branch $branch, CoachProfile $coachProfile): ClassSession
    {
        return ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'coach_profile_id' => $coachProfile->id,
            'session_date' => today()->toDateString(),
            'status' => 'scheduled',
        ]);
    }

    private function invoice(Branch $branch, int $total, ?StudentProfile $student = null): Invoice
    {
        return Invoice::factory()->create([
            'student_profile_id' => $student?->id ?? StudentProfile::factory()->create([
                'user_id' => User::factory()->create(['role' => 'member'])->id,
            ])->id,
            'branch_id' => $branch->id,
            'total_amount' => $total,
            'status' => 'unpaid',
        ]);
    }

    private function payment(Invoice $invoice, int $amount): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'recorded_by_user_id' => $invoice->studentProfile->user_id,
            'amount' => $amount,
            'status' => 'recorded',
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $invoice->update(['status' => $invoice->statusFromPayments()]);

        return $payment;
    }
}
