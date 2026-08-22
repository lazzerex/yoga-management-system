<?php

namespace App\Modules\Operations\Room\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Room;
use App\Modules\Operations\Room\Actions\CreateRoomAction;
use App\Modules\Operations\Room\Actions\DeleteRoomAction;
use App\Modules\Operations\Room\Actions\UpdateRoomAction;
use App\Modules\Operations\Room\Requests\StoreRoomRequest;
use App\Modules\Operations\Room\Requests\UpdateRoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class RoomController extends Controller
{
    public function create(Request $request): Response
    {
        return inertia('Operations/Rooms/Create', [
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'selectedBranchId' => $request->integer('branch_id') ?: null,
            'endpoints' => [
                'store' => route('operations.rooms.store'),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function edit(Room $room): Response
    {
        return inertia('Operations/Rooms/Edit', [
            'room' => [
                'id' => $room->id,
                'branch_id' => $room->branch_id,
                'name' => $room->name,
                'capacity' => $room->capacity,
                'is_active' => $room->is_active,
            ],
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'endpoints' => [
                'update' => route('operations.rooms.update', $room),
                'index' => route('operations.yoga-center'),
            ],
        ]);
    }

    public function store(StoreRoomRequest $request, CreateRoomAction $action): RedirectResponse
    {
        $room = $action->execute($request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.roomCreated', 'params' => ['name' => $room->name]]);
    }

    public function update(UpdateRoomRequest $request, Room $room, UpdateRoomAction $action): RedirectResponse
    {
        $action->execute($room, $request->validated());

        return redirect()
            ->route('operations.yoga-center')
            ->with('success', ['key' => 'flash.roomUpdated', 'params' => ['name' => $room->name]]);
    }

    public function destroy(Room $room, DeleteRoomAction $action): RedirectResponse
    {
        $name = $room->name;
        $action->execute($room);

        return back()->with('success', ['key' => 'flash.roomDeleted', 'params' => ['name' => $name]]);
    }
}
