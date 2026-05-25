<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Update only if last activity was more than 1 minute ago to reduce DB load
            if (!$user->last_activity || $user->last_activity->diffInMinutes(now()) >= 1) {
                $user->timestamps = false;
                $user->last_activity = now();
                $user->save();
            }
        }

        return $next($request);
    }
}
