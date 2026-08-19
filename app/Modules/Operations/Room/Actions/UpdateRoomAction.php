<?php

namespace App\Modules\Operations\Room\Actions;

use App\Models\Room;

class UpdateRoomAction
{
    public function execute(Room $room, array $validated): Room
    {
        $room->update($validated);

        return $room;
    }
}
