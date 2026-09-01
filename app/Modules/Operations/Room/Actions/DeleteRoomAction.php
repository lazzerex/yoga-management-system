<?php

namespace App\Modules\Operations\Room\Actions;

use App\Models\Room;
use Illuminate\Validation\ValidationException;

class DeleteRoomAction
{
    public function execute(Room $room): void
    {
        if ($room->classSchedules()->exists() || $room->classSessions()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.roomInUse'),
            ]);
        }

        $room->delete();
    }
}
