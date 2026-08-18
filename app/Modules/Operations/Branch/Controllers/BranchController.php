<?php

namespace App\Modules\Operations\Branch\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Modules\Operations\Branch\Actions\CreateBranchAction;
use App\Modules\Operations\Branch\Actions\DeleteBranchAction;
use App\Modules\Operations\Branch\Actions\UpdateBranchAction;
use App\Modules\Operations\Branch\Requests\StoreBranchRequest;
use App\Modules\Operations\Branch\Requests\UpdateBranchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $branches = Branch::orderBy('name')->paginate(20);
        $canManage = $request->user()->can('operations.center.manage');

        return inertia('Operations/YogaCenter', [
            'canManage' => $canManage,
            'branches' => $branches->through(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'is_active' => $branch->is_active,
            ]),
            'stats' => [
                'total' => Branch::count(),
                'active' => Branch::active()->count(),
            ],
            'endpoints' => [
                'create' => $canManage ? route('operations.branches.create') : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Operations/Branches/Create', [
            'endpoints' => [
                'store' => route('operations.branches.store'),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function edit(Branch $branch): Response
    {
        return inertia('Operations/Branches/Edit', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'is_active' => $branch->is_active,
            ],
            'endpoints' => [
                'update' => route('operations.branches.update', $branch),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function store(StoreBranchRequest $request, CreateBranchAction $action): RedirectResponse
    {
        $branch = $action->execute($request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.branchCreated', 'params' => ['name' => $branch->name]]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, UpdateBranchAction $action): RedirectResponse
    {
        $action->execute($branch, $request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.branchUpdated', 'params' => ['name' => $branch->name]]);
    }

    public function destroy(Branch $branch, DeleteBranchAction $action): RedirectResponse
    {
        $name = $branch->name;
        $action->execute($branch);

        return back()->with('success', ['key' => 'flash.branchDeleted', 'params' => ['name' => $name]]);
    }
}
