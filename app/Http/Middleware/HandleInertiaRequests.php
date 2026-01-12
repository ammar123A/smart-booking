<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'features' => fn () => [
                'auth' => [
                    'hasTermsAndPrivacyPolicyFeature' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                ],
                'profile' => [
                    'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
                    'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
                    'canUpdateProfileInformation' => Features::canUpdateProfileInformation(),
                    'hasEmailVerification' => Features::enabled(Features::emailVerification()),
                    'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
                    'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
                ],
                'api' => [
                    'hasApiFeatures' => Jetstream::hasApiFeatures(),
                ],
            ],
            'flash' => fn () => [
                'token' => $request->session()->get('flash.token'),
                'banner' => $request->session()->get('flash.banner'),
                'bannerStyle' => $request->session()->get('flash.bannerStyle'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'isAdmin' => fn () => (bool) ($request->user()?->hasRole('admin') ?? false),
            'roleNames' => fn () => $request->user()?->getRoleNames()?->values() ?? [],
        ];
    }
}
