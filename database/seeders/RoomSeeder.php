<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Room counts deliberately vary per branch so demo data isn't identical everywhere.
        $roomsByBranch = [
            'Downtown Studio' => [
                ['name' => 'Studio A', 'capacity' => 20],
                ['name' => 'Studio B', 'capacity' => 15],
                ['name' => 'Private Room', 'capacity' => 6],
            ],
            'Riverside Center' => [
                ['name' => 'Studio A', 'capacity' => 18],
                ['name' => 'Private Room', 'capacity' => 6],
            ],
            'Uptown Loft' => [
                ['name' => 'Studio A', 'capacity' => 12],
            ],
            'Westside Branch' => [
                ['name' => 'Studio A', 'capacity' => 15],
                ['name' => 'Studio B', 'capacity' => 10],
            ],
        ];

        foreach (Branch::orderBy('name')->get() as $branch) {
            foreach ($roomsByBranch[$branch->name] ?? [] as $room) {
                Room::updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => $room['name']],
                    ['capacity' => $room['capacity']]
                );
            }
        }
    }
}
