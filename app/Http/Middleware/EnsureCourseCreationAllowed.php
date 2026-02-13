<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourseCreationAllowed
{
    /**
     * Allow course creation if user is Super Admin OR if examiner creation is enabled in settings.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::find(session('admin_user_id'));
        
        if (!$user || !$user->isStaff()) {
            return redirect()->route('dashboard')
                ->with('error', 'Error');
        }

        // Super Admin always allowed
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Examiner: check if allowed by setting
        if ($user->isExaminer()) {
            $allowed = Setting::getValue(Setting::KEY_ALLOW_EXAMINER_CREATE_COURSE, '0') === '1';
            if (!$allowed) {
                return redirect()->route('dashboard')
                    ->with('error', 'Error');
            }
        }

        return $next($request);
    }
}
