<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Operations\LessonPlan\Controllers\LessonPlanController;
use App\Modules\Operations\StudentProfile\Controllers\StudentProfileController;
use App\Modules\Operations\Tuition\Controllers\InvoiceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const LIMIT = 5;

    private const MIN_LENGTH = 2;

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return response()->json(['groups' => []]);
        }

        $groups = array_values(array_filter([
            $this->students($request, $term),
            $this->coaches($request, $term),
            $this->invoices($request, $term),
            $this->lessonPlans($request, $term),
            $this->users($request, $term),
        ]));

        return response()->json(['groups' => $groups]);
    }

    /**
     * A group the viewer holds no permission for is absent, not empty.
     */
    private function students(Request $request, string $term): ?array
    {
        $user = $request->user();

        if ($user->cannot('operations.students.view')) {
            return null;
        }

        $canManage = $user->can('operations.students.manage');

        $rows = StudentProfile::with('user:id,name,username')
            ->tap(StudentProfileController::visibleScope($request))
            ->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%"))
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (StudentProfile $profile) => [
                'id' => $profile->id,
                'title' => $profile->user->name,
                'meta' => '@'.$profile->user->username,
                'url' => $canManage
                    ? route('operations.students.edit', $profile->id)
                    : route('operations.students.index', ['search' => $profile->user->name]),
            ]);

        return $this->group('students', $rows);
    }

    private function coaches(Request $request, string $term): ?array
    {
        $user = $request->user();

        if ($user->cannot('operations.coaches.view')) {
            return null;
        }

        $canManage = $user->can('operations.coaches.manage');

        $rows = CoachProfile::with('user:id,name,username')
            ->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%"))
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (CoachProfile $profile) => [
                'id' => $profile->id,
                'title' => $profile->user->name,
                'meta' => '@'.$profile->user->username,
                'url' => $canManage
                    ? route('operations.coaches.edit', $profile->id)
                    : route('operations.coaches.index', ['search' => $profile->user->name]),
            ]);

        return $this->group('coaches', $rows);
    }

    private function invoices(Request $request, string $term): ?array
    {
        if ($request->user()->cannot('operations.tuition.view')) {
            return null;
        }

        $rows = Invoice::with('studentProfile.user:id,name')
            ->tap(InvoiceController::branchScope($request))
            ->where(fn ($q) => $q
                ->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('studentProfile.user', fn ($u) => $u->where('name', 'like', "%{$term}%")))
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'title' => $invoice->invoice_number,
                'meta' => $invoice->studentProfile->user->name,
                'url' => route('operations.invoices.show', $invoice->id),
            ]);

        return $this->group('invoices', $rows);
    }

    private function lessonPlans(Request $request, string $term): ?array
    {
        if ($request->user()->cannot('operations.plans.view')) {
            return null;
        }

        $rows = LessonPlan::with('coachProfile.user:id,name')
            ->tap(LessonPlanController::visibleScope($request))
            ->where('title', 'like', "%{$term}%")
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (LessonPlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'meta' => $plan->coachProfile?->user?->name ?? '',
                'url' => route('operations.lesson-plans.show', $plan->id),
            ]);

        return $this->group('lessonPlans', $rows);
    }

    private function users(Request $request, string $term): ?array
    {
        if ($request->user()->cannot('admin.users.view')) {
            return null;
        }

        $rows = User::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"))
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'username'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'title' => $user->name,
                'meta' => '@'.$user->username,
                'url' => route('admin.users.edit', $user->id),
            ]);

        return $this->group('users', $rows);
    }

    private function group(string $key, mixed $rows): ?array
    {
        return $rows->isEmpty() ? null : ['key' => $key, 'rows' => $rows->all()];
    }

    /**
     * The box is hidden entirely when no group can ever return a row.
     */
    public static function isAvailableTo(?User $user): bool
    {
        return (bool) $user?->canAny([
            'operations.students.view',
            'operations.coaches.view',
            'operations.tuition.view',
            'operations.plans.view',
            'admin.users.view',
        ]);
    }
}
