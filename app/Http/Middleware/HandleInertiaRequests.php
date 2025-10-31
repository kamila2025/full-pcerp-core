<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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
        return array_merge(parent::share($request), [
            'params' => (object) array_merge($this->convertSearchToArray($request->input('search')), $request->except(array_values(config('repository.criteria.params', [])))),
            'auth' => [
                'user' => fn () => $request->user()?->only('id', 'name', 'email'),
                'abilities' => env('ROLE_PERMISSION_ENABLED') ? \App\Models\User::query()->find(auth()->id())?->getAllPermissions()->pluck('name')->push('read') : ['manage'],
            ],
            'brand' => env('APP_NAME'),
            'logo' => env('APP_ICON'),
            'version' => fn () => exec('git describe --tags'),
        ]);
    }

    /**
     * Convert search string to array
     */
    protected function convertSearchToArray(?string $search = ''): array
    {
        if (empty($search)) return [];

        return collect(explode(';', $search))
            ->mapWithKeys(function ($pair) {
                $parts = explode(':', $pair);
                return [$parts[0] => $parts[1] ?? ''];
            })
            ->toArray();
    }
}
