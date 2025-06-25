<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        $user = $request->user();
        if ($user) {
            $user->load('roles.permissions', 'team');
            $permissions = $user?->roles->first()?->permissions
                ->pluck('name') // assuming 'title' == 'name'
                ->mapWithKeys(fn($name) => [$name => true])
                ->all();
            $teams = Team::all();
            //dd($teams);
        }


        //dd($permissions);
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'teams' => $teams ?? [],
            ],
            'permissions' =>  $permissions ?? [],
            'currentRoutePath' => $request->path(),
            'translations' => __('sidebar'), // this comes from the lang folder in resources
        ];
    }
}
