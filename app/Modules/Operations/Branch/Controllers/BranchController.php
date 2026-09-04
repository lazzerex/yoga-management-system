<?php

namespace App\Modules\Operations\Branch\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassType;
use App\Models\Room;
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
        $currentBranchId = $request->attributes->get('currentBranch')?->id;
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        // One search box drives all three tabs: they are three views of the same centre.
        $filter = fn ($query) => $query
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'));

        $branches = Branch::query()->tap($filter)->orderBy('name')->paginate(20, pageName: 'branchesPage')->withQueryString();
        $rooms = Room::with('branch:id,name')
            ->when($currentBranchId, fn ($q) => $q->where('branch_id', $currentBranchId))
            ->tap($filter)
            ->orderBy('name')
            ->paginate(20, pageName: 'roomsPage')
            ->withQueryString();
        $classTypes = ClassType::query()->tap($filter)->orderBy('name')->paginate(20, pageName: 'classTypesPage')->withQueryString();
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
            'branchStats' => [
                'total' => Branch::count(),
                'active' => Branch::active()->count(),
            ],
            'rooms' => $rooms->through(fn (Room $room) => [
                'id' => $room->id,
                'branch_id' => $room->branch_id,
                'branch_name' => $room->branch->name,
                'name' => $room->name,
                'capacity' => $room->capacity,
                'is_active' => $room->is_active,
            ]),
            'roomStats' => [
                'total' => Room::when($currentBranchId, fn ($q) => $q->where('branch_id', $currentBranchId))->count(),
                'active' => Room::active()->when($currentBranchId, fn ($q) => $q->where('branch_id', $currentBranchId))->count(),
            ],
            'classTypes' => $classTypes->through(fn (ClassType $classType) => [
                'id' => $classType->id,
                'name' => $classType->name,
                'description' => $classType->description,
                'is_active' => $classType->is_active,
            ]),
            'classTypeStats' => [
                'total' => ClassType::count(),
                'active' => ClassType::active()->count(),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'endpoints' => [
                'createBranch' => $canManage ? route('operations.branches.create') : null,
                'createRoom' => $canManage ? route('operations.rooms.create') : null,
                'createClassType' => $canManage ? route('operations.class-types.create') : null,
                'index' => route('operations.yoga-center'),
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
