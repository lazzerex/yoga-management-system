<?php

namespace App\Modules\Operations\LessonPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\User;
use App\Modules\Operations\LessonPlan\Actions\CreateLessonPlanAction;
use App\Modules\Operations\LessonPlan\Actions\DeleteLessonPlanAction;
use App\Modules\Operations\LessonPlan\Actions\ReviewLessonPlanAction;
use App\Modules\Operations\LessonPlan\Actions\SubmitLessonPlanAction;
use App\Modules\Operations\LessonPlan\Actions\UpdateLessonPlanAction;
use App\Modules\Operations\LessonPlan\Requests\ReviewLessonPlanRequest;
use App\Modules\Operations\LessonPlan\Requests\StoreLessonPlanRequest;
use App\Modules\Operations\LessonPlan\Requests\UpdateLessonPlanRequest;
use App\Modules\Operations\Media\Actions\AuthorizeMediaAccessAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class LessonPlanController extends Controller
{
    public function __construct(private AuthorizeMediaAccessAction $access) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $scope = $this->visibleScope($request);

        $plans = LessonPlan::with(['classType:id,name', 'branch:id,name', 'coachProfile.user:id,name'])
            ->tap($scope)
            ->orderByDesc('id')
            ->paginate(20);

        $counts = LessonPlan::query()->tap($scope)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $canManage = $this->authorProfile($user) !== null;
        $canReview = $user->can('operations.plans.review');

        return inertia('Operations/LessonPlanning', [
            'plans' => $plans->through(fn (LessonPlan $plan) => $this->row($plan)),
            'stats' => collect(LessonPlan::STATUSES)->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)]),
            'canManage' => $canManage,
            'endpoints' => [
                'create' => $canManage ? route('operations.lesson-plans.create') : null,
                'pending' => $canReview ? route('operations.lesson-plans.pending') : null,
            ],
        ]);
    }

    public function pending(Request $request): Response
    {
        $branchId = $request->attributes->get('currentBranch')?->id;

        $plans = LessonPlan::with(['classType:id,name', 'branch:id,name', 'coachProfile.user:id,name'])
            ->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('submitted_at')
            ->paginate(20);

        return inertia('Operations/LessonPlans/Pending', [
            'plans' => $plans->through(fn (LessonPlan $plan) => $this->row($plan)),
            'endpoints' => [
                'index' => route('operations.lesson-planning'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->authorProfile($request->user()) !== null, 403);

        return inertia('Operations/LessonPlans/Create', [
            'options' => $this->formOptions($request),
            'selectedBranchId' => $request->attributes->get('currentBranch')?->id,
            'endpoints' => [
                'store' => route('operations.lesson-plans.store'),
                'index' => route('operations.lesson-planning'),
            ],
        ]);
    }

    public function store(StoreLessonPlanRequest $request, CreateLessonPlanAction $action): RedirectResponse
    {
        $coachProfile = $this->authorProfile($request->user());
        abort_unless($coachProfile !== null, 403);

        $plan = $action->execute($request->validated(), $coachProfile);

        return redirect()
            ->route('operations.lesson-plans.show', $plan)
            ->with('success', ['key' => 'flash.lessonPlanCreated', 'params' => ['title' => $plan->title]]);
    }

    public function show(Request $request, LessonPlan $lessonPlan): Response
    {
        $user = $request->user();
        abort_unless($this->canView($user, $lessonPlan), 403);

        $lessonPlan->load([
            'classType:id,name',
            'branch:id,name',
            'coachProfile.user:id,name',
            'classSession.classType:id,name',
            'reviews.reviewer:id,name',
            'media',
        ]);

        $owns = $this->owns($user, $lessonPlan);
        $canReview = $user->can('operations.plans.review')
            && $lessonPlan->status === 'pending'
            && $lessonPlan->coachProfile->user_id !== $user->id;

        return inertia('Operations/LessonPlans/Show', [
            'plan' => $this->row($lessonPlan) + [
                'objective' => $lessonPlan->objective,
                'asana_sequence' => $lessonPlan->asana_sequence,
                'session_label' => $lessonPlan->classSession ? $this->sessionLabel($lessonPlan->classSession) : null,
                'attachments' => $this->attachments($user, $lessonPlan),
            ],
            'reviews' => $lessonPlan->reviews->sortByDesc('reviewed_at')->values()->map(fn ($review) => [
                'id' => $review->id,
                'action' => $review->action,
                'comment' => $review->comment,
                'reviewer_name' => $review->reviewer?->name,
                'reviewed_at' => $review->reviewed_at->toIso8601String(),
            ]),
            'endpoints' => [
                'edit' => $owns && $lessonPlan->isEditable() ? route('operations.lesson-plans.edit', $lessonPlan) : null,
                'submit' => $owns && $lessonPlan->isEditable() ? route('operations.lesson-plans.submit', $lessonPlan) : null,
                'destroy' => $owns && $lessonPlan->status === 'draft' ? route('operations.lesson-plans.destroy', $lessonPlan) : null,
                'review' => $canReview ? route('operations.lesson-plans.review', $lessonPlan) : null,
                'index' => route('operations.lesson-planning'),
            ],
        ]);
    }

    public function edit(Request $request, LessonPlan $lessonPlan): Response
    {
        abort_unless($this->owns($request->user(), $lessonPlan) && $lessonPlan->isEditable(), 403);

        $lessonPlan->load('media');

        return inertia('Operations/LessonPlans/Edit', [
            'plan' => [
                'id' => $lessonPlan->id,
                'branch_id' => $lessonPlan->branch_id,
                'class_type_id' => $lessonPlan->class_type_id,
                'class_session_id' => $lessonPlan->class_session_id,
                'title' => $lessonPlan->title,
                'objective' => $lessonPlan->objective,
                'asana_sequence' => $lessonPlan->asana_sequence,
                'duration_minutes' => $lessonPlan->duration_minutes,
                'level' => $lessonPlan->level,
                'status' => $lessonPlan->status,
                'attachments' => $this->attachments($request->user(), $lessonPlan),
                'max_attachments' => LessonPlan::MAX_ATTACHMENTS,
            ],
            'options' => $this->formOptions($request),
            'endpoints' => [
                'update' => route('operations.lesson-plans.update', $lessonPlan),
                'show' => route('operations.lesson-plans.show', $lessonPlan),
            ],
        ]);
    }

    public function update(UpdateLessonPlanRequest $request, LessonPlan $lessonPlan, UpdateLessonPlanAction $action): RedirectResponse
    {
        abort_unless($this->owns($request->user(), $lessonPlan), 403);

        $action->execute($lessonPlan, $request->validated());

        return redirect()
            ->route('operations.lesson-plans.show', $lessonPlan)
            ->with('success', ['key' => 'flash.lessonPlanUpdated', 'params' => ['title' => $lessonPlan->title]]);
    }

    public function destroy(Request $request, LessonPlan $lessonPlan, DeleteLessonPlanAction $action): RedirectResponse
    {
        abort_unless($this->owns($request->user(), $lessonPlan), 403);

        $title = $lessonPlan->title;
        $action->execute($lessonPlan);

        return redirect()
            ->route('operations.lesson-planning')
            ->with('success', ['key' => 'flash.lessonPlanDeleted', 'params' => ['title' => $title]]);
    }

    public function submit(Request $request, LessonPlan $lessonPlan, SubmitLessonPlanAction $action): RedirectResponse
    {
        abort_unless($this->owns($request->user(), $lessonPlan), 403);

        $action->execute($lessonPlan);

        return back()->with('success', ['key' => 'flash.lessonPlanSubmitted']);
    }

    public function review(ReviewLessonPlanRequest $request, LessonPlan $lessonPlan, ReviewLessonPlanAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $action->execute($lessonPlan, $validated, $request->user());

        return back()->with('success', [
            'key' => $validated['action'] === 'approved' ? 'flash.lessonPlanApproved' : 'flash.lessonPlanRejected',
        ]);
    }

    private function attachments(User $user, LessonPlan $plan): array
    {
        return $plan->getMedia('attachments')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'size' => (int) $media->size,
            'showUrl' => route('operations.files.show', $media),
            'deleteUrl' => $this->access->canDelete($user, $media) ? route('operations.files.destroy', $media) : null,
        ])->all();
    }

    private function row(LessonPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'class_type_name' => $plan->classType->name,
            'branch_name' => $plan->branch->name,
            'coach_name' => $plan->coachProfile->user->name,
            'level' => $plan->level,
            'status' => $plan->status,
            'duration_minutes' => $plan->duration_minutes,
            'submitted_at' => $plan->submitted_at?->toIso8601String(),
            'showUrl' => route('operations.lesson-plans.show', $plan->id),
        ];
    }

    private function formOptions(Request $request): array
    {
        $coachProfile = $this->authorProfile($request->user());

        return [
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name']),
            'sessions' => $coachProfile ? $this->sessionOptions($coachProfile) : collect(),
            'levels' => LessonPlan::LEVELS,
        ];
    }

    private function sessionOptions(CoachProfile $coachProfile): Collection
    {
        return ClassSession::with('classType:id,name')
            ->where('coach_profile_id', $coachProfile->id)
            ->upcoming()
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(50)
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'label' => $this->sessionLabel($session),
            ]);
    }

    private function sessionLabel(ClassSession $session): string
    {
        return $session->session_date.' '.substr($session->start_time, 0, 5).' - '.$session->classType->name;
    }

    // Authoring is coach-only by design: admins review plans, they never write them.
    private function authorProfile(User $user): ?CoachProfile
    {
        return $user->can('operations.plans.manage') ? $user->coachProfile : null;
    }

    private function visibleScope(Request $request): callable
    {
        $user = $request->user();
        $branchId = $request->attributes->get('currentBranch')?->id;
        $seesEveryone = $user->can('operations.plans.view.any');

        return fn ($query) => $query
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(! $seesEveryone, fn ($q) => $q->where('coach_profile_id', $user->coachProfile?->id));
    }

    private function canView(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('operations.plans.view.any')
            || $lessonPlan->coach_profile_id === $user->coachProfile?->id;
    }

    private function owns(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('operations.plans.manage')
            && $lessonPlan->coach_profile_id === $user->coachProfile?->id;
    }
}
