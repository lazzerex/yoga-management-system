<?php

namespace App\Modules\Operations\Enrollment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Modules\Admin\User\Actions\AuditUserAction;
use App\Modules\Operations\Enrollment\Actions\CancelEnrollmentAction;
use App\Modules\Operations\Enrollment\Actions\CreateEnrollmentAction;
use App\Modules\Operations\Enrollment\Actions\ResolveEntitlementAction;
use App\Modules\Operations\Tuition\Actions\StudentEntitlementsAction;
use App\Notifications\EnrollmentCancelledByStaffNotification;
use App\Support\Settings;
use App\Support\Table\SortsQueries;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Response;

class EnrollmentController extends Controller
{
    use SortsQueries;

    public function index(Request $request): Response
    {
        $studentProfile = $request->user()->studentProfile;
        $cutoffHours = (int) Settings::get('booking.cancel_cutoff_hours', config('enrollment.cancel_cutoff_hours'));

        $myEnrollments = $studentProfile
            ? Enrollment::with([
                'classSession' => fn ($q) => $q->withCount([
                    'enrollments as session_booked_count' => fn ($q) => $q->where('status', 'booked'),
                    'enrollments as session_waitlist_count' => fn ($q) => $q->where('status', 'waitlisted'),
                ]),
                'classSession.branch:id,name,address',
                'classSession.room:id,name,capacity',
                'classSession.classType:id,name,description',
                'classSession.coachProfile:id,user_id,bio,years_experience',
                'classSession.coachProfile.user:id,name',
            ])
                ->where('student_profile_id', $studentProfile->id)
                ->where('status', '!=', 'cancelled')
                ->whereHas('classSession', fn ($q) => $q->where('session_date', '>=', now()->toDateString()))
                ->get()
                ->sortBy(fn (Enrollment $e) => $e->classSession->session_date.$e->classSession->start_time)
                ->values()
            : collect();

        $waitlistRanks = $myEnrollments
            ->where('status', 'waitlisted')
            ->mapWithKeys(fn (Enrollment $e) => [
                $e->id => Enrollment::where('class_session_id', $e->class_session_id)
                    ->where('status', 'waitlisted')
                    ->where('enrolled_at', '<', $e->enrolled_at)
                    ->count() + 1,
            ]);

        return inertia('Member/MyClasses', [
            'myEnrollments' => $myEnrollments->map(function (Enrollment $e) use ($cutoffHours, $waitlistRanks) {
                $session = $e->classSession;
                $start = Carbon::parse($session->session_date.' '.$session->start_time);

                return [
                    'id' => $e->id,
                    'status' => $e->status,
                    'waitlist_position' => $waitlistRanks->get($e->id),
                    'can_cancel' => now()->addHours($cutoffHours)->lessThanOrEqualTo($start),
                    'cancel_deadline' => $start->copy()->subHours($cutoffHours)->toIso8601String(),
                    'enrolled_at' => $e->enrolled_at?->toIso8601String(),
                    'session_date' => $session->session_date,
                    'start_time' => substr($session->start_time, 0, 5),
                    'end_time' => substr($session->end_time, 0, 5),
                    'class_type_id' => $session->class_type_id,
                    'class_type_name' => $session->classType->name,
                    'class_type_description' => $session->classType->description,
                    'coach_name' => $session->coachProfile->user->name,
                    'coach_bio' => $session->coachProfile->bio,
                    'coach_years_experience' => $session->coachProfile->years_experience,
                    'branch_name' => $session->branch->name,
                    'branch_address' => $session->branch->address,
                    'room_name' => $session->room->name,
                    'room_capacity' => $session->room->capacity,
                    'capacity' => $session->capacity,
                    'booked_count' => $session->session_booked_count,
                    'waitlist_count' => $session->session_waitlist_count,
                    'spots_left' => max(0, $session->capacity - $session->session_booked_count),
                    'cancelUrl' => route('member.enrollments.destroy', $e->id),
                ];
            }),
            'openSessionsCount' => $this->availableSessionsQuery($request)->count(),
            'cancelCutoffHours' => $cutoffHours,
        ]);
    }

    public function browse(
        Request $request,
        ResolveEntitlementAction $resolve,
        StudentEntitlementsAction $entitlements,
    ): Response {
        // Resolved once, then asked about each session in memory.
        $set = $resolve->forStudent($request->user()->studentProfile);

        $availableSessions = $this->availableSessionsQuery($request)
            ->with(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name'])
            ->withCount([
                'enrollments as booked_count' => fn ($q) => $q->where('status', 'booked'),
                'enrollments as waitlist_count' => fn ($q) => $q->where('status', 'waitlisted'),
            ])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        return inertia('Member/BookClass', [
            'availableSessions' => $availableSessions->map(fn (ClassSession $s) => [
                'id' => $s->id,
                'session_date' => $s->session_date,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'class_type_id' => $s->class_type_id,
                'class_type_name' => $s->classType->name,
                'coach_profile_id' => $s->coach_profile_id,
                'coach_name' => $s->coachProfile->user->name,
                'branch_name' => $s->branch->name,
                'room_name' => $s->room->name,
                'capacity' => $s->capacity,
                'booked_count' => $s->booked_count,
                'spots_left' => max(0, $s->capacity - $s->booked_count),
                'waitlist_count' => $s->waitlist_count,
                'block_reason' => $set->check($s->session_date),
                'bookUrl' => route('member.enrollments.store', $s->id),
            ])->values(),
            'entitlements' => $entitlements->execute($request->user()->studentProfile?->id),
        ]);
    }

    private function availableSessionsQuery(Request $request)
    {
        $studentProfile = $request->user()->studentProfile;
        $branchId = $request->attributes->get('currentBranch')?->id;

        $enrolledSessionIds = $studentProfile
            ? Enrollment::where('student_profile_id', $studentProfile->id)->where('status', '!=', 'cancelled')->pluck('class_session_id')
            : collect();

        return ClassSession::query()
            ->upcoming()
            ->where('status', 'scheduled')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('id', $enrolledSessionIds);
    }

    public function mySchedule(Request $request): Response
    {
        $studentProfile = $request->user()->studentProfile;

        $sessions = $studentProfile
            ? ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name'])
                ->whereHas('enrollments', fn ($q) => $q->where('student_profile_id', $studentProfile->id)->where('status', 'booked'))
                ->where('status', '!=', 'cancelled')
                ->whereBetween('session_date', [now()->toDateString(), now()->addDays(6)->toDateString()])
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return inertia('Member/MySchedule', [
            'sessions' => $sessions->map(fn (ClassSession $s) => [
                'session_date' => $s->session_date,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'class_type_name' => $s->classType->name,
                'branch_name' => $s->branch->name,
                'room_name' => $s->room->name,
            ]),
        ]);
    }

    public function adminIndex(Request $request): Response
    {
        $branchId = $request->attributes->get('currentBranch')?->id;
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();
        $from = $this->validDate($request->string('from')->toString());
        $to = $this->validDate($request->string('to')->toString());

        $query = Enrollment::query()
            ->with(['studentProfile.user:id,name', 'classSession.classType:id,name', 'classSession.coachProfile.user:id,name'])
            ->whereHas('classSession', fn ($q) => $q
                ->when($branchId, fn ($b) => $b->where('branch_id', $branchId))
                ->when($from, fn ($b) => $b->where('session_date', '>=', $from))
                ->when($to, fn ($b) => $b->where('session_date', '<=', $to)))
            ->when(in_array($status, ['booked', 'waitlisted', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->whereHas('studentProfile.user', fn ($u) => $u->where('name', 'like', "%{$search}%")));

        $sort = $this->applySort($query, $request, [
            'status' => ['booked', 'waitlisted', 'cancelled'],
            'enrolled_at' => 'enrolled_at',
        ], 'enrolled_at');

        $enrollments = $query->paginate(20)->withQueryString();

        return inertia('Operations/Enrollments/Index', [
            'enrollments' => $enrollments->through(fn (Enrollment $e) => [
                'id' => $e->id,
                'status' => $e->status,
                'student_name' => $e->studentProfile->user->name,
                'class_type_name' => $e->classSession->classType->name,
                'coach_name' => $e->classSession->coachProfile->user->name,
                'session_date' => $e->classSession->session_date,
                'start_time' => substr($e->classSession->start_time, 0, 5),
                'enrolled_at' => $e->enrolled_at?->toDateTimeString(),
                'cancelUrl' => $e->status !== 'cancelled' ? route('operations.enrollments.admin-cancel', $e->id) : null,
            ]),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'from' => $from ?? '',
                'to' => $to ?? '',
            ] + $sort,
            'endpoints' => ['index' => route('operations.enrollments.index')],
        ]);
    }

    public function store(Request $request, ClassSession $classSession, CreateEnrollmentAction $action): RedirectResponse
    {
        $studentProfile = $request->user()->studentProfile;
        abort_unless($studentProfile, 403);

        $enrollment = $action->execute($studentProfile, $classSession);

        $key = $enrollment->status === 'waitlisted' ? 'flash.enrollmentWaitlisted' : 'flash.enrollmentBooked';

        return back()->with('success', ['key' => $key]);
    }

    public function destroy(Request $request, Enrollment $enrollment, CancelEnrollmentAction $action): RedirectResponse
    {
        abort_unless($enrollment->student_profile_id === $request->user()->studentProfile?->id, 403);

        $action->execute($enrollment);

        return back()->with('success', ['key' => 'flash.enrollmentCancelled']);
    }

    public function adminCancel(Request $request, Enrollment $enrollment, CancelEnrollmentAction $action, AuditUserAction $audit): RedirectResponse
    {
        $enrollment->load(['studentProfile.user:id,name', 'classSession.classType:id,name']);
        $previousStatus = $enrollment->status;

        // Staff are not bound by the member cutoff.
        $action->execute($enrollment, enforceCutoff: false);

        $audit->execute($request->user(), 'cancel_enrollment', $enrollment->studentProfile->user, [
            'enrollment_id' => $enrollment->id,
            'from' => $previousStatus,
            'class' => $enrollment->classSession->classType->name,
            'session_date' => $enrollment->classSession->session_date,
        ]);

        // Not in the Action: it also serves the member cancelling their own booking.
        Notification::send(
            $enrollment->studentProfile->user,
            new EnrollmentCancelledByStaffNotification($enrollment)
        );

        return back()->with('success', ['key' => 'flash.enrollmentCancelled']);
    }

    /** A filter value that is not a date is ignored rather than reaching the query. */
    private function validDate(string $value): ?string
    {
        try {
            return $value === '' ? null : Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
