<?php

namespace App\Modules\Operations\Room\Actions;

use App\Models\Room;

class DeleteRoomAction
{
    public function execute(Room $room): void
    {
        $room->delete();
    }
}
