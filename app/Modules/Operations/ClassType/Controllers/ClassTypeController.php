<?php

namespace App\Modules\Operations\ClassType\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use App\Modules\Operations\ClassType\Actions\CreateClassTypeAction;
use App\Modules\Operations\ClassType\Actions\DeleteClassTypeAction;
use App\Modules\Operations\ClassType\Actions\UpdateClassTypeAction;
use App\Modules\Operations\ClassType\Requests\StoreClassTypeRequest;
use App\Modules\Operations\ClassType\Requests\UpdateClassTypeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class ClassTypeController extends Controller
{
    public function create(): Response
    {
        return inertia('Operations/ClassTypes/Create', [
            'endpoints' => [
                'store' => route('operations.class-types.store'),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function edit(ClassType $classType): Response
    {
        return inertia('Operations/ClassTypes/Edit', [
            'classType' => [
                'id' => $classType->id,
                'name' => $classType->name,
                'description' => $classType->description,
                'is_active' => $classType->is_active,
            ],
            'endpoints' => [
                'update' => route('operations.class-types.update', $classType),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function store(StoreClassTypeRequest $request, CreateClassTypeAction $action): RedirectResponse
    {
        $classType = $action->execute($request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.classTypeCreated', 'params' => ['name' => $classType->name]]);
    }

    public function update(UpdateClassTypeRequest $request, ClassType $classType, UpdateClassTypeAction $action): RedirectResponse
    {
        $action->execute($classType, $request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.classTypeUpdated', 'params' => ['name' => $classType->name]]);
    }

    public function destroy(ClassType $classType, DeleteClassTypeAction $action): RedirectResponse
    {
        $name = $classType->name;
        $action->execute($classType);

        return back()->with('success', ['key' => 'flash.classTypeDeleted', 'params' => ['name' => $name]]);
    }
}
