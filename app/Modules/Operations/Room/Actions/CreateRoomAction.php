<?php

namespace App\Modules\Operations\Room\Actions;

use App\Models\Room;

class CreateRoomAction
{
    public function execute(array $validated): Room
    {
        return Room::create($validated);
    }
}
