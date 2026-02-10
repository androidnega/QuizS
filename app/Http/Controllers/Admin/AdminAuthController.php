<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAuthController extends Controller
{
    /**
     * Show login form (admin/examiner). If already logged in, send to intended URL or dashboard (no redirect away from requested page).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Prevent login if already authenticated as admin/examiner
        if (session('admin_authenticated', false)) {
            $role = session('admin_role');
            $dashboard = route('dashboard');
            return redirect()->intended($dashboard);
        }
        
        // Prevent login if student is already logged in
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as a student. Please logout first to login as staff.');
        }
        
        return view('admin.login');
    }

    /**
     * Authenticate against users table only (admin/examiner). No env fallback.
     */
    public function login(Request $request): RedirectResponse
    {
        // Prevent login if already authenticated as admin/examiner
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }
        
        // Prevent login if student is already logged in
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as a student. Please logout first to login as staff.');
        }
        
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session([
                'admin_authenticated' => true,
                'admin_user_id' => $user->id,
                'admin_role' => $user->role,
            ]);
            $dashboard = route('dashboard');
            return redirect()->intended($dashboard)->with('success', 'Welcome back.');
        }

        return back()->withInput($request->only('username'))
            ->with('error', 'Invalid username or password.');
    }

    /**
     * Log out.
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role']);

        return redirect()->route('login')
            ->with('info', 'You have been logged out.');
    }
}
