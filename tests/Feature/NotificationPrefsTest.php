<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationPrefsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_user_without_preferences_gets_the_catalogue_default(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $this->assertTrue($user->wantsNotification('tuition.overdue', 'mail'));
        $this->assertFalse($user->wantsNotification('class.reminder', 'mail'));
        $this->assertTrue($user->wantsNotification('class.reminder', 'database'));
    }

    public function test_a_stored_deviation_overrides_the_default(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'notification_preferences' => [
                'tuition.overdue' => ['mail' => false],
                'class.reminder' => ['mail' => true],
            ],
        ]);

        $this->assertFalse($user->wantsNotification('tuition.overdue', 'mail'));
        $this->assertTrue($user->wantsNotification('class.reminder', 'mail'));
    }

    public function test_a_deviation_on_one_channel_leaves_the_other_alone(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'notification_preferences' => ['tuition.overdue' => ['mail' => false]],
        ]);

        $this->assertTrue($user->wantsNotification('tuition.overdue', 'database'));
    }

    public function test_an_uncatalogued_event_reaches_nobody(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'notification_preferences' => ['not.an.event' => ['mail' => true]],
        ]);

        $this->assertFalse($user->wantsNotification('not.an.event', 'mail'));
        $this->assertFalse($user->wantsNotification('tuition.overdue', 'carrier_pigeon'));
    }

    public function test_every_catalogued_event_names_permissions_that_exist(): void
    {
        $known = Permission::pluck('name')->all();

        foreach (config('notifications.events') as $key => $event) {
            $this->assertNotEmpty($event['audience'], "{$key} reaches nobody");

            foreach ($event['audience'] as $permission) {
                $this->assertContains($permission, $known, "{$key} names a permission that does not exist");
            }
        }
    }

    public function test_switching_language_writes_the_column_the_cookie_cannot_reach(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $this->assertNull($user->locale);

        $this->actingAs($user)
            ->post('/cms/locale', ['locale' => 'vi'])
            ->assertRedirect();

        $this->assertSame('vi', $user->fresh()->locale);
    }

    public function test_an_unknown_locale_is_refused(): void
    {
        $user = User::factory()->create(['role' => 'member', 'locale' => 'vi']);

        $this->actingAs($user)
            ->post('/cms/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('vi', $user->fresh()->locale);
    }

    public function test_a_guest_cannot_set_a_locale(): void
    {
        $this->post('/cms/locale', ['locale' => 'vi'])->assertRedirect('/cms/login');
    }

    public function test_the_dispatch_key_refuses_the_same_send_twice_in_a_day(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $row = [
            'event_key' => 'tuition.overdue',
            'notifiable_id' => $user->id,
            'subject_type' => 'App\Models\Invoice',
            'subject_id' => 1,
            'sent_on' => '2026-09-06',
        ];

        DB::table('notification_dispatches')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('notification_dispatches')->insert($row);
    }

    public function test_the_same_subject_can_be_sent_again_on_another_day(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        foreach (['2026-09-06', '2026-09-07'] as $day) {
            DB::table('notification_dispatches')->insert([
                'event_key' => 'tuition.overdue',
                'notifiable_id' => $user->id,
                'subject_type' => 'App\Models\Invoice',
                'subject_id' => 1,
                'sent_on' => $day,
            ]);
        }

        $this->assertSame(2, DB::table('notification_dispatches')->count());
    }
}
