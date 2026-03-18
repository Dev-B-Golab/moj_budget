<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoRestriction
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->email === 'demo@example.com' && $request->isMethod('GET') === false) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', 'Konto demo nie pozwala na edycję danych. Utwórz własne konto, aby korzystać z pełnej funkcjonalności.');
            }

            return response()->json([
                'message' => 'Konto demo nie pozwala na edycję danych.',
            ], 403);
        }

        return $next($request);
    }
}
