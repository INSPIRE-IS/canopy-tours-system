<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIntegrationDocsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $request->user()?->roles()
            ->whereHas('permissions', fn ($permissions) => $permissions
                ->where('name', 'integration_api_docs_access'))
            ->exists() ?? false;

        abort_unless($allowed, 403);

        return $next($request);
    }
}
