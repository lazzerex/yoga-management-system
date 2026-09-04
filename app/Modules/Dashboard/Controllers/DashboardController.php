<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Dashboard\Actions\AdminWidgetsAction;
use App\Modules\Dashboard\Actions\AttendanceTrendsAction;
use App\Modules\Dashboard\Actions\CentreOverviewAction;
use App\Modules\Dashboard\Actions\CoachWidgetsAction;
use App\Modules\Dashboard\Actions\FinancialsAction;
use App\Modules\Dashboard\Actions\MemberWidgetsAction;
use App\Modules\Dashboard\Actions\MyProgressAction;
use App\Modules\Dashboard\Actions\MyTeachingAction;
use App\Modules\Dashboard\Actions\PeopleInsightsAction;
use App\Modules\Dashboard\Actions\StudioPulseAction;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    /** Month windows the overview offers. */
    private const WINDOWS = [3, 6, 12];

    /**
     * A tab is a view key the server knows, not a slug derived from its label: renaming
     * a tab in the editor must not point it at a view that does not exist.
     *
     * @var array<string, array{persona: string, permission: ?string, labelKey: string}>
     */
    private const VIEWS = [
        'overview' => ['persona' => 'admin', 'permission' => 'operations.sessions.view', 'labelKey' => 'dashboard.viewOverview'],
        'dashboard' => ['persona' => 'any', 'permission' => null, 'labelKey' => 'dashboard.viewDashboard'],
        'classes' => ['persona' => 'admin', 'permission' => 'operations.sessions.view', 'labelKey' => 'dashboard.viewClasses'],
        'people' => ['persona' => 'admin', 'permission' => 'operations.students.view.any', 'labelKey' => 'dashboard.viewPeople'],
        'attendance' => ['persona' => 'admin', 'permission' => 'operations.attendance.view', 'labelKey' => 'dashboard.viewAttendance'],
        'financials' => ['persona' => 'admin', 'permission' => 'operations.tuition.view', 'labelKey' => 'dashboard.viewFinancials'],
        'my-teaching' => ['persona' => 'coach', 'permission' => 'coach.dashboard.view', 'labelKey' => 'dashboard.viewMyTeaching'],
        'my-progress' => ['persona' => 'member', 'permission' => 'member.dashboard.view', 'labelKey' => 'dashboard.viewMyProgress'],
    ];

    public function __construct(
        private CentreOverviewAction $centreOverview,
        private AdminWidgetsAction $adminWidgets,
        private CoachWidgetsAction $coachWidgets,
        private MemberWidgetsAction $memberWidgets,
        private StudioPulseAction $studioPulse,
        private PeopleInsightsAction $peopleInsights,
        private AttendanceTrendsAction $attendanceTrends,
        private FinancialsAction $financials,
        private MyTeachingAction $myTeaching,
        private MyProgressAction $myProgress,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $branchId = $request->attributes->get('currentBranch')?->id;
        $persona = $this->persona($user);
        $tabs = $this->tabs($user, $persona);

        $requested = $request->string('view')->toString();
        // The first tab a viewer may open is their landing page: for an admin that is
        // the centre-wide overview, for everyone else their own homepage.
        $view = in_array($requested, array_column($tabs, 'viewKey'), true)
            ? $requested
            : ($tabs[0]['viewKey'] ?? 'dashboard');

        $overviewFilters = [
            'branch_id' => $request->integer('branch_id') ?: '',
            'months' => in_array($request->integer('months'), self::WINDOWS, true) ? $request->integer('months') : 6,
        ];

        return inertia('Dashboard', [
            'persona' => $persona,
            'view' => $view,
            'tabs' => $tabs,
            'filters' => $overviewFilters,
            'options' => ['windows' => self::WINDOWS],
            'widgets' => $view === 'dashboard' ? $this->widgets($user, $persona, $branchId) : [],
            'analytics' => $view === 'dashboard' ? null : $this->analytics($view, $user, $branchId, $overviewFilters),
        ]);
    }

    private function persona(User $user): string
    {
        return match (true) {
            $user->canAccessAdmin() => 'admin',
            $user->can('coach.dashboard.view') => 'coach',
            default => 'member',
        };
    }

    /** @return array<int, array{viewKey: string, labelKey: string}> */
    private function tabs(User $user, string $persona): array
    {
        return collect(self::VIEWS)
            ->filter(fn (array $view) => $view['persona'] === 'any' || $view['persona'] === $persona)
            ->filter(fn (array $view) => $view['permission'] === null || $user->can($view['permission']))
            ->map(fn (array $view, string $key) => ['viewKey' => $key, 'labelKey' => $view['labelKey']])
            ->values()
            ->all();
    }

    /** The persona picks the shell; the permission inside each action picks what fills it. */
    private function widgets(User $user, string $persona, ?int $branchId): array
    {
        return match ($persona) {
            'admin' => $this->adminWidgets->execute($user, $branchId),
            'coach' => $this->coachWidgets->execute($user),
            default => $this->memberWidgets->execute($user),
        };
    }

    private function analytics(string $view, User $user, ?int $branchId, array $filters): array
    {
        return match ($view) {
            // The overview answers across branches, so it ignores the header switcher
            // and reads its own branch filter instead.
            'overview' => $this->centreOverview->execute($user, $filters['branch_id'] ?: null, $filters['months']),
            'classes' => $this->studioPulse->execute($branchId),
            'people' => $this->peopleInsights->execute($branchId),
            'attendance' => $this->attendanceTrends->execute($branchId, null),
            'financials' => $this->financials->execute($branchId),
            'my-teaching' => [
                'teaching' => $this->myTeaching->execute($user->coachProfile?->id),
                'attendance' => $this->attendanceTrends->execute(null, $user->coachProfile?->id),
            ],
            default => $this->myProgress->execute($user->studentProfile?->id),
        };
    }
}
