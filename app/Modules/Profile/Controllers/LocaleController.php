<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,vi'],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return back();
    }
}
