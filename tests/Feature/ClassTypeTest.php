<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTypeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_class_type(): void
    {
        $this->actingAs($this->admin())
            ->post('/cms/operations/class-types', [
                'name' => 'Hatha Yoga',
                'description' => 'Slow-paced.',
                'is_active' => true,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertDatabaseHas('class_types', ['name' => 'Hatha Yoga']);
    }

    public function test_class_type_name_must_be_unique(): void
    {
        ClassType::factory()->create(['name' => 'Vinyasa Flow']);

        $this->actingAs($this->admin())
            ->from('/cms/operations/class-types/create')
            ->post('/cms/operations/class-types', [
                'name' => 'Vinyasa Flow',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, ClassType::where('name', 'Vinyasa Flow')->count());
    }

    public function test_admin_can_update_a_class_type(): void
    {
        $classType = ClassType::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-types/{$classType->id}", [
                'name' => 'New Name',
                'is_active' => false,
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $classType->refresh();
        $this->assertSame('New Name', $classType->name);
        $this->assertFalse($classType->is_active);
    }

    public function test_updating_a_class_type_without_changing_name_does_not_fail_uniqueness(): void
    {
        $classType = ClassType::factory()->create(['name' => 'Yin Yoga']);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-types/{$classType->id}", [
                'name' => 'Yin Yoga',
                'description' => 'Updated description.',
            ])
            ->assertRedirect('/cms/operations/yoga-center');

        $this->assertSame('Updated description.', $classType->fresh()->description);
    }

    public function test_admin_can_delete_a_class_type(): void
    {
        $classType = ClassType::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/class-types/{$classType->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('class_types', ['id' => $classType->id]);
    }

    public function test_a_class_type_used_by_a_session_cannot_be_deleted(): void
    {
        $session = ClassSession::factory()->create();

        $this->actingAs($this->admin())
            ->from('/cms/operations/yoga-center')
            ->delete("/cms/operations/class-types/{$session->class_type_id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('class_types', ['id' => $session->class_type_id]);
    }

    public function test_coach_can_view_class_types_but_cannot_manage_them(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        ClassType::factory()->create();

        $this->actingAs($coach)->get('/cms/operations/yoga-center')->assertOk();
        $this->actingAs($coach)->get('/cms/operations/class-types/create')->assertForbidden();
        $this->actingAs($coach)->post('/cms/operations/class-types', [
            'name' => 'Sneaky Class Type',
        ])->assertForbidden();

        $this->assertDatabaseMissing('class_types', ['name' => 'Sneaky Class Type']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
