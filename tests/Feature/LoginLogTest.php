<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_successful_login_is_recorded_in_login_logs(): void
    {
        $password = 'Password123!';

        $user = User::factory()->create([
            'username' => 'coachlina',
            'password' => Hash::make($password),
        ]);

        $this->post('/cms/login', [
            'username' => $user->username,
            'password' => $password,
        ])->assertRedirect('/cms/dashboard');

        $log = LoginLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(LoginLog::STATUS_SUCCESS, $log->status);
        $this->assertSame($user->username, $log->attempted_identifier);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertNull($log->failure_reason);
    }

    public function test_failed_login_with_wrong_password_is_recorded_in_login_logs(): void
    {
        $user = User::factory()->create([
            'username' => 'membercasey',
            'password' => Hash::make('ActualPass123!'),
        ]);

        $this->from('/cms/login')->post('/cms/login', [
            'username' => $user->username,
            'password' => 'WrongPass123!',
        ])->assertSessionHasErrors('username');

        $log = LoginLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(LoginLog::STATUS_FAILED, $log->status);
        $this->assertSame('wrong_password', $log->failure_reason);
        $this->assertSame($user->username, $log->attempted_identifier);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_failed_login_with_unknown_user_is_recorded_in_login_logs(): void
    {
        $this->from('/cms/login')->post('/cms/login', [
            'username' => 'ghost-user',
            'password' => 'WrongPass123!',
        ])->assertSessionHasErrors('username');

        $log = LoginLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(LoginLog::STATUS_FAILED, $log->status);
        $this->assertSame('user_not_found', $log->failure_reason);
        $this->assertSame('ghost-user', $log->attempted_identifier);
        $this->assertNull($log->user_id);
    }

    public function test_repeated_failed_attempts_are_recorded_before_throttle_threshold(): void
    {
        $user = User::factory()->create([
            'username' => 'nothrottlecase',
            'password' => Hash::make('ActualPass123!'),
        ]);

        for ($index = 0; $index < 5; $index += 1) {
            $this->from('/cms/login')->post('/cms/login', [
                'username' => $user->username,
                'password' => 'WrongPass123!',
            ])->assertSessionHasErrors('username');
        }

        $log = LoginLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(LoginLog::STATUS_FAILED, $log->status);
        $this->assertSame('wrong_password', $log->failure_reason);
    }
}
