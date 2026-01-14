<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority order: Session > User preference > Browser > Default
        $locale = Session::get('locale');

        if (!$locale && $request->user()) {
            $locale = $request->user()->locale;
        }

        if (!$locale) {
            $locale = $request->getPreferredLanguage(['en', 'ms', 'zh-CN']);
        }

        $locale = $locale ?: config('app.locale');

        // Validate locale
        $availableLocales = ['en', 'ms', 'zh-CN'];
        if (!in_array($locale, $availableLocales)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
