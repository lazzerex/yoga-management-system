<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $branch = Branch::active()->find((int) $request->cookie('branch_id'))
                ?? Branch::active()->orderBy('name')->first();

            $request->attributes->set('currentBranch', $branch);
        }

        return $next($request);
    }
}
