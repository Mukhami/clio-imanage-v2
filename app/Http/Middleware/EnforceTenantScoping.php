<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantScoping
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id !== null) {
            // Tenant-scoped user — store tenant context for the request
            app()->instance('current.tenant_id', $user->tenant_id);
        }

        return $next($request);
    }
}
