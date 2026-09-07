<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (! in_array($locale, ['en', 'vi'], true)) {
            $locale = $request->user()?->locale ?? Settings::get('centre.default_locale', 'en');
        }

        if (in_array($locale, ['en', 'vi'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
