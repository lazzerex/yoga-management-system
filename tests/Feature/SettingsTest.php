<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Operations\Enrollment\Actions\CancelEnrollmentAction;
use App\Support\Settings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_the_seeder_grants_the_write_permission_to_admin_only(): void
    {
        $this->assertSame(35, Permission::count());
        $this->assertSame(30, Role::findByName('admin')->permissions()->count());
        $this->assertSame(10, Role::findByName('coach')->permissions()->count());
        $this->assertSame(4, Role::findByName('member')->permissions()->count());
    }

    public function test_an_admin_saves_the_live_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/cms/admin/settings', [
                'centre_name' => 'Lotus Yoga',
                'cancel_cutoff_hours' => 6,
                'default_locale' => 'vi',
            ])
            ->assertRedirect();

        $this->assertSame('Lotus Yoga', Settings::get('centre.name'));
        $this->assertSame('6', Settings::get('booking.cancel_cutoff_hours'));
        $this->assertSame('vi', Settings::get('centre.default_locale'));

        // Every setting changed, one row.
        $this->assertSame(1, AuditLog::where('action', 'update_setting')->count());

        $this->assertSame([
            ['key' => 'centre.name', 'from' => '', 'to' => 'Lotus Yoga'],
            ['key' => 'booking.cancel_cutoff_hours', 'from' => '', 'to' => '6'],
            ['key' => 'centre.default_locale', 'from' => '', 'to' => 'vi'],
        ], AuditLog::where('action', 'update_setting')->sole()->meta['changes']);
    }

    public function test_a_saved_cutoff_moves_the_cancellation_window(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $session = ClassSession::factory()->create([
            'session_date' => now()->addHours(4)->toDateString(),
            'start_time' => now()->addHours(4)->format('H:i:s'),
        ]);
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => StudentProfile::factory()->create()->id,
            'status' => 'booked',
        ]);

        // The config default is 2 hours, so a class four hours out is still cancellable.
        $this->actingAs($admin)->post('/cms/admin/settings', [
            'centre_name' => 'Lotus Yoga',
            'cancel_cutoff_hours' => 8,
            'default_locale' => 'en',
        ]);

        $this->expectException(ValidationException::class);
        app(CancelEnrollmentAction::class)->execute($enrollment);
    }

    public function test_the_accessor_reads_a_written_value_back_through_a_fresh_read(): void
    {
        $this->assertNull(Settings::get('centre.name'));

        Settings::set('centre.name', 'Lotus Yoga');
        $this->assertSame('Lotus Yoga', Settings::get('centre.name'));

        Settings::set('centre.name', 'Lotus Studio');
        $this->assertSame('Lotus Studio', Settings::get('centre.name'));
    }

    public function test_a_viewer_without_the_write_permission_cannot_save(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $this->actingAs($coach)
            ->post('/cms/admin/settings', [
                'centre_name' => 'Lotus Yoga',
                'cancel_cutoff_hours' => 6,
                'default_locale' => 'en',
            ])
            ->assertForbidden();

        $this->assertNull(Settings::get('centre.name'));
    }

    public function test_an_unknown_key_in_the_payload_is_not_stored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/cms/admin/settings', [
            'centre_name' => 'Lotus Yoga',
            'cancel_cutoff_hours' => 6,
            'default_locale' => 'en',
            'maintenance_mode' => '1',
        ]);

        $this->assertDatabaseMissing('settings', ['key' => 'maintenance_mode']);
        $this->assertDatabaseMissing('settings', ['key' => 'maintenance.mode']);
    }

    public function test_the_centre_name_prop_renders_in_the_viewer_locale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // No stored name: the fallback is a translation, and share() runs before SetLocale.
        $this->actingAs($admin)
            ->withUnencryptedCookie('locale', 'vi')
            ->get('/cms/admin/settings/general')
            ->assertInertia(fn (Assert $page) => $page->where('centreName', __('dashboard.systemName', [], 'vi')));

        Settings::set('centre.name', 'Lotus Yoga');

        $this->actingAs($admin)
            ->withUnencryptedCookie('locale', 'vi')
            ->get('/cms/admin/settings/general')
            ->assertInertia(fn (Assert $page) => $page->where('centreName', 'Lotus Yoga'));
    }

    public function test_a_stored_user_locale_applies_when_no_cookie_is_present(): void
    {
        Settings::set('centre.default_locale', 'vi');
        $guestFacing = User::factory()->create(['role' => 'admin', 'locale' => 'en']);

        $this->actingAs($guestFacing)->get('/cms/admin/settings/general')->assertOk();
        $this->assertSame('en', app()->getLocale());

        $viUser = User::factory()->create(['role' => 'admin', 'locale' => 'vi']);
        $this->actingAs($viUser)->get('/cms/admin/settings/general')->assertOk();
        $this->assertSame('vi', app()->getLocale());
    }
}
