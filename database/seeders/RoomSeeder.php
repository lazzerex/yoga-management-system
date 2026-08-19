<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::orderBy('name')->get();

        if ($branches->isEmpty()) {
            return;
        }

        $rooms = [
            ['name' => 'Studio A', 'capacity' => 20],
            ['name' => 'Studio B', 'capacity' => 15],
            ['name' => 'Private Room', 'capacity' => 6],
        ];

        foreach ($branches as $branch) {
            foreach ($rooms as $room) {
                Room::updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => $room['name']],
                    ['capacity' => $room['capacity']]
                );
            }
        }
    }
}
