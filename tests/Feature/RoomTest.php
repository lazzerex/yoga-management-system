<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_room(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->admin())
            ->post('/cms/operations/rooms', [
                'branch_id' => $branch->id,
                'name' => 'Studio A',
                'capacity' => 20,
                'is_active' => true,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertDatabaseHas('rooms', [
            'branch_id' => $branch->id,
            'name' => 'Studio A',
            'capacity' => 20,
        ]);
    }

    public function test_room_name_must_be_unique_per_branch(): void
    {
        $branch = Branch::factory()->create();
        Room::factory()->create(['branch_id' => $branch->id, 'name' => 'Studio A']);

        $this->actingAs($this->admin())
            ->from('/cms/operations/rooms/create')
            ->post('/cms/operations/rooms', [
                'branch_id' => $branch->id,
                'name' => 'Studio A',
                'capacity' => 10,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Room::where('branch_id', $branch->id)->where('name', 'Studio A')->count());
    }

    public function test_same_room_name_is_allowed_in_a_different_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Room::factory()->create(['branch_id' => $branchA->id, 'name' => 'Studio A']);

        $this->actingAs($this->admin())
            ->post('/cms/operations/rooms', [
                'branch_id' => $branchB->id,
                'name' => 'Studio A',
                'capacity' => 10,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertSame(2, Room::where('name', 'Studio A')->count());
    }

    public function test_admin_can_update_a_room(): void
    {
        $room = Room::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/rooms/{$room->id}", [
                'branch_id' => $room->branch_id,
                'name' => 'New Name',
                'capacity' => $room->capacity,
                'is_active' => false,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $room->refresh();
        $this->assertSame('New Name', $room->name);
        $this->assertFalse($room->is_active);
    }

    public function test_updating_a_room_without_changing_name_does_not_fail_uniqueness(): void
    {
        $room = Room::factory()->create(['name' => 'Studio A', 'capacity' => 15]);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/rooms/{$room->id}", [
                'branch_id' => $room->branch_id,
                'name' => 'Studio A',
                'capacity' => 18,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertSame(18, $room->fresh()->capacity);
    }

    public function test_admin_can_delete_a_room(): void
    {
        $room = Room::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/rooms/{$room->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_a_room_used_by_a_session_cannot_be_deleted(): void
    {
        $session = ClassSession::factory()->create();

        $this->actingAs($this->admin())
            ->from('/cms/operations/yoga-center')
            ->delete("/cms/operations/rooms/{$session->room_id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('rooms', ['id' => $session->room_id]);
    }

    public function test_coach_can_view_rooms_but_cannot_manage_them(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $branch = Branch::factory()->create();
        Room::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($coach)->get('/cms/operations/yoga-center')->assertOk();
        $this->actingAs($coach)->get('/cms/operations/rooms/create')->assertForbidden();
        $this->actingAs($coach)->post('/cms/operations/rooms', [
            'branch_id' => $branch->id,
            'name' => 'Sneaky Room',
            'capacity' => 5,
        ])->assertForbidden();

        $this->assertDatabaseMissing('rooms', ['name' => 'Sneaky Room']);
    }

    public function test_yoga_center_page_scopes_rooms_to_the_current_branch_cookie(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Room::factory()->create(['branch_id' => $branchA->id, 'name' => 'Room A']);
        Room::factory()->create(['branch_id' => $branchB->id, 'name' => 'Room B']);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $branchA->id)
            ->get('/cms/operations/yoga-center')
            ->viewData('page');

        $roomNames = collect($page['props']['rooms']['data'])->pluck('name');
        $this->assertTrue($roomNames->contains('Room A'));
        $this->assertFalse($roomNames->contains('Room B'));
        $this->assertSame($branchA->id, $page['props']['currentBranch']['id']);
    }

    public function test_yoga_center_page_defaults_to_the_first_branch_without_a_cookie(): void
    {
        $branchA = Branch::factory()->create(['name' => 'Alpha Studio']);
        Branch::factory()->create(['name' => 'Zeta Studio']);
        Room::factory()->create(['branch_id' => $branchA->id, 'name' => 'Room A']);

        $page = $this->actingAs($this->admin())
            ->get('/cms/operations/yoga-center')
            ->viewData('page');

        $this->assertSame($branchA->id, $page['props']['currentBranch']['id']);
        $roomNames = collect($page['props']['rooms']['data'])->pluck('name');
        $this->assertTrue($roomNames->contains('Room A'));
    }

    public function test_room_create_page_prefills_branch_from_current_branch_cookie(): void
    {
        $branch = Branch::factory()->create();

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $branch->id)
            ->get('/cms/operations/rooms/create')
            ->viewData('page');

        $this->assertSame($branch->id, $page['props']['selectedBranchId']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
