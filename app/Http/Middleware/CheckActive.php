<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está inactiva.']);
        }
        return $next($request);
    }
}
