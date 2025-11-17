<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Check2FAUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the '2fa_user_id' session key exists
        if (!session()->has('2fa_user_id')) {
            // If not, redirect to the login page
            return redirect()->route('login');
        }

        return $next($request);
    }
}
