<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Require staff authentication for admin routes (except login).
     * Validates session against database: user must exist and have role super_admin or examiner.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login') || $request->routeIs('login.post')) {
            return $next($request);
        }

        if (!session('admin_authenticated', false)) {
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to access the dashboard.');
        }

        $user = User::find(session('admin_user_id'));
        if (!$user || !$user->isStaff()) {
            session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);
            return redirect()->guest(route('login'))
                ->with('error', 'Session invalid or access revoked. Please log in again.');
        }

        // Keep session role in sync with database
        session(['admin_role' => $user->role]);

        // Set user for this request so policies and auth()->user() work
        auth()->setUser($user);

        return $next($request);
    }
}
