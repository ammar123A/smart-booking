<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:en,ms,zh-CN,ta'],
        ]);

        $locale = $request->input('locale');

        // Store in session
        Session::put('locale', $locale);

        // Update user preference if authenticated
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }
}
