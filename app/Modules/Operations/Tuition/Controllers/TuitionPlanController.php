<?php

namespace App\Modules\Operations\Tuition\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\TuitionPlan;
use App\Modules\Operations\Tuition\Actions\CreateTuitionPlanAction;
use App\Modules\Operations\Tuition\Actions\DeleteTuitionPlanAction;
use App\Modules\Operations\Tuition\Actions\UpdateTuitionPlanAction;
use App\Modules\Operations\Tuition\Requests\StoreTuitionPlanRequest;
use App\Modules\Operations\Tuition\Requests\UpdateTuitionPlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class TuitionPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();

        $plans = TuitionPlan::with('branch:id,name')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when(in_array($type, TuitionPlan::TYPES, true), fn ($q) => $q->where('type', $type))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return inertia('Operations/Tuition/Plans/Index', [
            'plans' => $plans->through(fn (TuitionPlan $plan) => $this->row($plan)),
            'filters' => ['search' => $search, 'type' => $type, 'status' => $status],
            'options' => ['types' => TuitionPlan::TYPES],
            'endpoints' => [
                'create' => route('operations.tuition-plans.create'),
                'invoices' => route('operations.tuition-fees'),
                'index' => route('operations.tuition-plans.index'),
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Operations/Tuition/Plans/Create', [
            'options' => $this->formOptions(),
            'endpoints' => [
                'store' => route('operations.tuition-plans.store'),
                'index' => route('operations.tuition-plans.index'),
            ],
        ]);
    }

    public function store(StoreTuitionPlanRequest $request, CreateTuitionPlanAction $action): RedirectResponse
    {
        $plan = $action->execute($request->validated());

        return redirect()
            ->route('operations.tuition-plans.index')
            ->with('success', ['key' => 'flash.tuitionPlanCreated', 'params' => ['name' => $plan->name]]);
    }

    public function edit(TuitionPlan $tuitionPlan): Response
    {
        return inertia('Operations/Tuition/Plans/Edit', [
            'plan' => [
                'id' => $tuitionPlan->id,
                'branch_id' => $tuitionPlan->branch_id,
                'name' => $tuitionPlan->name,
                'type' => $tuitionPlan->type,
                'price_amount' => $tuitionPlan->price_amount,
                'session_count' => $tuitionPlan->session_count,
                'duration_days' => $tuitionPlan->duration_days,
                'description' => $tuitionPlan->description,
                'is_active' => $tuitionPlan->is_active,
            ],
            'options' => $this->formOptions(),
            'endpoints' => [
                'update' => route('operations.tuition-plans.update', $tuitionPlan),
                'index' => route('operations.tuition-plans.index'),
            ],
        ]);
    }

    public function update(UpdateTuitionPlanRequest $request, TuitionPlan $tuitionPlan, UpdateTuitionPlanAction $action): RedirectResponse
    {
        $action->execute($tuitionPlan, $request->validated());

        return redirect()
            ->route('operations.tuition-plans.index')
            ->with('success', ['key' => 'flash.tuitionPlanUpdated', 'params' => ['name' => $tuitionPlan->name]]);
    }

    public function destroy(TuitionPlan $tuitionPlan, DeleteTuitionPlanAction $action): RedirectResponse
    {
        $name = $tuitionPlan->name;
        $action->execute($tuitionPlan);

        return redirect()
            ->route('operations.tuition-plans.index')
            ->with('success', ['key' => 'flash.tuitionPlanDeleted', 'params' => ['name' => $name]]);
    }

    private function row(TuitionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'type' => $plan->type,
            'price_amount' => $plan->price_amount,
            'session_count' => $plan->session_count,
            'duration_days' => $plan->duration_days,
            'branch_name' => $plan->branch?->name,
            'is_active' => $plan->is_active,
            'editUrl' => route('operations.tuition-plans.edit', $plan->id),
            'destroyUrl' => route('operations.tuition-plans.destroy', $plan->id),
        ];
    }

    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'types' => TuitionPlan::TYPES,
        ];
    }
}
