<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalPasswordIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();

        if ($actor?->must_change_password && ! $request->routeIs('portal.password.force-change*', 'portal.logout')) {
            return redirect()->route('portal.password.force-change');
        }

        return $next($request);
    }
}
