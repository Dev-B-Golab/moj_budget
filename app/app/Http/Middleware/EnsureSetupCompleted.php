<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->email !== 'demo@example.com'
            && !$user->setup_completed_at
            && !$request->routeIs('setup.*')
            && !$request->routeIs('logout')
            && !$request->routeIs('profile.*')
        ) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
