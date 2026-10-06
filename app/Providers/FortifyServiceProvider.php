<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use App\Support\LoginAttemptLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::loginView(fn () => inertia('Auth/Login', [
            'endpoints' => [
                'login' => route('login'),
                'register' => route('register'),
            ],
            'status' => session('status'),
        ]));
        Fortify::requestPasswordResetLinkView(fn () => inertia('Auth/ForgotPassword', [
            'status' => session('status'),
        ]));
        Fortify::confirmPasswordView(fn () => inertia('Auth/ConfirmPassword'));
        Fortify::resetPasswordView(fn (Request $request) => inertia('Auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => (string) $request->query('email', ''),
        ]));
        Fortify::registerView(fn () => inertia('Auth/Register', [
            'endpoints' => [
                'register' => route('register'),
                'login' => route('login'),
            ],
        ]));

        Fortify::authenticateUsing(function (Request $request) {
            $identifier = trim((string) $request->input('username', ''));
            $user = User::where('username', $identifier)->first();

            if (! $user) {
                LoginAttemptLogger::recordFailed($request, 'user_not_found');

                return null;
            }

            if (! Hash::check((string) $request->input('password', ''), $user->password)) {
                LoginAttemptLogger::recordFailed($request, 'wrong_password', $user);

                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
