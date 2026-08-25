<?php

namespace App\Modules\Operations\Enrollment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Modules\Operations\Enrollment\Actions\CancelEnrollmentAction;
use App\Modules\Operations\Enrollment\Actions\CreateEnrollmentAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $studentProfile = $request->user()->studentProfile;
        $branchId = $request->attributes->get('currentBranch')?->id;

        $myEnrollments = $studentProfile
            ? Enrollment::with(['classSession.branch:id,name', 'classSession.room:id,name', 'classSession.classType:id,name', 'classSession.coachProfile.user:id,name'])
                ->where('student_profile_id', $studentProfile->id)
                ->where('status', '!=', 'cancelled')
                ->whereHas('classSession', fn ($q) => $q->where('session_date', '>=', now()->toDateString()))
                ->get()
                ->sortBy(fn (Enrollment $e) => $e->classSession->session_date.$e->classSession->start_time)
                ->values()
            : collect();

        $enrolledSessionIds = $studentProfile
            ? Enrollment::where('student_profile_id', $studentProfile->id)->where('status', '!=', 'cancelled')->pluck('class_session_id')
            : collect();

        $availableSessions = ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name'])
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->upcoming()
            ->where('status', 'scheduled')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('id', $enrolledSessionIds)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate(10);

        return inertia('Member/MyClasses', [
            'myEnrollments' => $myEnrollments->map(fn (Enrollment $e) => [
                'id' => $e->id,
                'status' => $e->status,
                'session_date' => $e->classSession->session_date,
                'start_time' => substr($e->classSession->start_time, 0, 5),
                'class_type_name' => $e->classSession->classType->name,
                'coach_name' => $e->classSession->coachProfile->user->name,
                'branch_name' => $e->classSession->branch->name,
                'cancelUrl' => route('member.enrollments.destroy', $e->id),
            ]),
            'availableSessions' => $availableSessions->through(fn (ClassSession $s) => [
                'id' => $s->id,
                'session_date' => $s->session_date,
                'start_time' => substr($s->start_time, 0, 5),
                'class_type_name' => $s->classType->name,
                'coach_name' => $s->coachProfile->user->name,
                'branch_name' => $s->branch->name,
                'room_name' => $s->room->name,
                'isFull' => $s->booked_count >= $s->capacity,
                'bookUrl' => route('member.enrollments.store', $s->id),
            ]),
        ]);
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
                'class_type_name' => $s->classType->name,
                'branch_name' => $s->branch->name,
                'room_name' => $s->room->name,
            ]),
        ]);
    }

    public function store(Request $request, ClassSession $classSession, CreateEnrollmentAction $action): RedirectResponse
    {
        $studentProfile = $request->user()->studentProfile;
        abort_unless($studentProfile, 403);

        $action->execute($studentProfile, $classSession);

        return back()->with('success', ['key' => 'flash.enrollmentCreated']);
    }

    public function destroy(Request $request, Enrollment $enrollment, CancelEnrollmentAction $action): RedirectResponse
    {
        abort_unless($enrollment->student_profile_id === $request->user()->studentProfile?->id, 403);

        $action->execute($enrollment);

        return back()->with('success', ['key' => 'flash.enrollmentCancelled']);
    }

    public function adminCancel(Enrollment $enrollment, CancelEnrollmentAction $action): RedirectResponse
    {
        $action->execute($enrollment);

        return back()->with('success', ['key' => 'flash.enrollmentCancelled']);
    }
}
