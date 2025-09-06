<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->active) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => __('auth.not_active'), // Create this lang line if it doesn't exist
            ]);
        }

        return $next($request);
    }
}

