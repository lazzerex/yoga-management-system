<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Without no-store the browser keeps a response, so pressing Back after a logout can
 * redisplay the previous user's screen. This covers the full page, Inertia's JSON page
 * object and any JSON endpoint alike: an Inertia visit carries exactly the same data as
 * the document it replaces, so marking only text/html would leave half the app exposed.
 *
 * Streamed and file responses are left alone. They are the avatars and downloads, and
 * caching them is what stops every list page refetching each thumbnail.
 */
class PreventPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || $response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
