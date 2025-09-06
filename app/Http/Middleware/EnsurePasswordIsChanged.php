<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();


        if ($request->routeIs('user.settings') || $request->routeIs('logout')) {
            return $next($request);
        }


        if (!$user->password_changed) {
            session()->flash('force_password_notice', 'Please update your password to continue.');
            return redirect()->to(route('user.settings') . '?tab=password-reset');
        }

        return $next($request);
    }
}

