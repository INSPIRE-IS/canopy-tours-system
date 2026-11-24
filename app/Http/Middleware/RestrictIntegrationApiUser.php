<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictIntegrationApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $isIntegrationUser = $request->user()?->roles()
            ->where('name', 'Integration API')
            ->exists() ?? false;

        if ($isIntegrationUser && ! $request->routeIs([
            'integration.everlytic.docs',
            'integration.everlytic.openapi',
            'integration.everlytic.token.store',
            'logout',
        ])) {
            return redirect()->route('integration.everlytic.docs');
        }

        return $next($request);
    }
}
