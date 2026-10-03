<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_branch(): void
    {
        $this->actingAs($this->admin())
            ->post('/cms/operations/branches', [
                'name' => 'Riverside Center',
                'address' => '19 River Blvd',
                'phone' => '0901234567',
                'is_active' => true,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertDatabaseHas('branches', [
            'name' => 'Riverside Center',
            'address' => '19 River Blvd',
        ]);
    }

    public function test_branch_name_must_be_unique(): void
    {
        Branch::factory()->create(['name' => 'Downtown Studio']);

        $this->actingAs($this->admin())
            ->from('/cms/operations/branches/create')
            ->post('/cms/operations/branches', [
                'name' => 'Downtown Studio',
                'address' => '1 Main St',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Branch::where('name', 'Downtown Studio')->count());
    }

    public function test_admin_can_update_a_branch(): void
    {
        $branch = Branch::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/branches/{$branch->id}", [
                'name' => 'New Name',
                'address' => $branch->address,
                'is_active' => false,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $branch->refresh();
        $this->assertSame('New Name', $branch->name);
        $this->assertFalse($branch->is_active);
    }

    public function test_admin_can_delete_a_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/branches/{$branch->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_coach_can_view_branches_but_cannot_manage_them(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        Branch::factory()->create();

        $this->actingAs($coach)->get('/cms/operations/yoga-center')->assertOk();
        $this->actingAs($coach)->get('/cms/operations/branches/create')->assertForbidden();
        $this->actingAs($coach)->post('/cms/operations/branches', [
            'name' => 'Sneaky Branch',
            'address' => 'Nowhere',
        ])->assertForbidden();

        $this->assertDatabaseMissing('branches', ['name' => 'Sneaky Branch']);
    }

    public function test_manage_actions_are_hidden_from_users_without_the_permission(): void
    {
        Branch::factory()->create();

        $coachPage = $this->actingAs(User::factory()->create(['role' => 'coach']))
            ->get('/cms/operations/yoga-center')->viewData('page');
        $adminPage = $this->actingAs($this->admin())
            ->get('/cms/operations/yoga-center')->viewData('page');

        $this->assertFalse($coachPage['props']['canManage']);
        $this->assertNull($coachPage['props']['endpoints']['createBranch']);
        $this->assertTrue($adminPage['props']['canManage']);
        $this->assertNotNull($adminPage['props']['endpoints']['createBranch']);
    }

    public function test_branch_with_rooms_cannot_be_deleted(): void
    {
        $branch = Branch::factory()->create();
        Room::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($this->admin())
            ->delete("/cms/operations/branches/{$branch->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
