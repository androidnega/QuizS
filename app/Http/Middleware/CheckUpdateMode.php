<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUpdateMode
{
    /**
     * When update mode is on: only allow staff login routes and already-logged-in staff.
     * Everyone else sees the maintenance page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $updateMode = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        if (! $updateMode) {
            return $next($request);
        }

        $path = $request->path();
        $allowedPaths = ['login', 'password/forgot', 'password/reset'];
        foreach ($allowedPaths as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return $next($request);
            }
        }

        if (session('admin_authenticated') && session('admin_user_id')) {
            $user = User::find(session('admin_user_id'));
            if ($user && $user->isStaff()) {
                return $next($request);
            }
        }

        return response()->view('maintenance', [], 503);
    }
}
