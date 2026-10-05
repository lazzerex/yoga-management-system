<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        // The intended URL may have been recorded for whoever was signed in before.
        $intended = $request->session()->pull('url.intended');

        return redirect()->to(
            $intended && $this->canOpen($request->user(), $intended) ? $intended : Fortify::redirects('login')
        );
    }

    private function canOpen(User $user, string $url): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
        } catch (HttpException) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')
                && ! $user->canAny(explode('|', substr($middleware, strlen('permission:'))))) {
                return false;
            }
        }

        return true;
    }
}
