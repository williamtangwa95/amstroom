<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveUserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (Auth::user()->status === 'inactive') {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been disabled. Please contact your administrator.',
                ]);
            }

            // Enforce 15 minutes inactivity timeout (15 minutes = 900 seconds)
            $lastActivity = session('last_user_activity');
            $maxIdleSeconds = 15 * 60; // 15 minutes

            if ($lastActivity && (time() - $lastActivity > $maxIdleSeconds)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'message' => 'Session expired due to 15 minutes of inactivity.',
                        'redirect' => route('login')
                    ], 401);
                }

                return redirect()->route('login')->with('info', 'You have been automatically logged out due to 15 minutes of inactivity.');
            }

            // Exclude background polling routes from extending the inactivity timer
            if (!$request->routeIs('notifications.poll', 'chats.unread-badge')) {
                session(['last_user_activity' => time()]);
            }
        }

        return $next($request);
    }
}
