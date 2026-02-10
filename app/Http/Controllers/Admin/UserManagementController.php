<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Super Admin sees all users, Examiners only see themselves
        $query = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])
            ->with('courses');
        
        if (!$isSuperAdmin && $user) {
            $query->where('id', $user->id);
        }
        
        $users = $query->orderBy('role')
            ->orderBy('username')
            ->paginate(20);
        
        // Get courses for the create modal (only courses examiner has access to)
        $courseIds = $user ? $user->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        
        return view('admin.users.index', compact('users', 'isSuperAdmin', 'courses'));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can create users.');
        }
        
        $courseIds = $user ? $user->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        return view('admin.users.create', compact('courses', 'isSuperAdmin'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can create users.');
        }
        
        $courseIds = $user ? $user->assignedCourseIds() : [];
        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'role' => 'required|in:super_admin,examiner',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
        
        // Only Super Admin can assign courses, and only to courses they have access to
        if ($isSuperAdmin) {
            $rules['course_ids'] = 'nullable|array';
            $rules['course_ids.*'] = 'exists:courses,id';
        }
        
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ]);

        $attrs = [
            'username' => $request->username,
            'name' => $request->name ?: $request->username,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ];
        if (Schema::hasColumn('users', 'email')) {
            $attrs['email'] = $request->filled('email') ? trim($request->email) : null;
        }
        $newUser = User::create($attrs);
        if ($newUser->isExaminer() && $request->filled('course_ids')) {
            $newUser->courses()->sync($request->input('course_ids', []));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot edit this user.');
        }
        
        // Examiners can only edit themselves
        if (!$isSuperAdmin && $currentUser && $currentUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        
        $user->load('courses');
        $courseIds = $currentUser ? $currentUser->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        return view('admin.users.edit', compact('user', 'courses', 'isSuperAdmin'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot edit this user.');
        }
        
        // Examiners can only edit themselves
        if (!$isSuperAdmin && $currentUser && $currentUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        
        // Examiners cannot change their role
        $rules = [
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
        ];
        
        // Only Super Admin can change roles
        if ($isSuperAdmin) {
            $rules['role'] = 'required|in:super_admin,examiner';
        }
        
        // Only Super Admin can assign courses
        if ($isSuperAdmin) {
            $rules['course_ids'] = 'nullable|array';
            $rules['course_ids.*'] = 'exists:courses,id';
        }
        
        // Super Admin can set/reset password for any staff (super_admin or examiner).
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }
        
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ]);

        $user->username = $request->username;
        if (Schema::hasColumn('users', 'email')) {
            $user->email = $request->filled('email') ? trim($request->email) : null;
        }
        $user->name = $request->name ?: $user->username;
        
        // Only Super Admin can change roles
        if ($isSuperAdmin) {
            $user->role = $request->role;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        
        // Only Super Admin can assign courses
        if ($isSuperAdmin && $user->isExaminer() && $request->filled('course_ids')) {
            $user->courses()->sync($request->input('course_ids', []));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User updated.');
    }

    /**
     * Show password prompt to view/reset examiner password.
     */
    public function showPasswordForm(User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can view/reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can view user passwords.');
        }
        
        return view('admin.users.view-password', compact('user'));
    }

    /**
     * Verify admin password and generate/reset examiner password.
     * Generates a temporary password that can be viewed once.
     */
    public function viewPassword(Request $request, User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can view/reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can view user passwords.');
        }
        
        $request->validate([
            'admin_password' => 'required|string',
            'action' => 'nullable|in:generate,reset',
            'new_password' => 'nullable|string|min:8',
            'new_password_confirmation' => 'nullable|required_with:new_password|same:new_password',
        ]);
        
        // Verify admin's password
        if (!Hash::check($request->admin_password, $currentUser->password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Incorrect admin password. Please try again.');
        }
        
        // Generate a random password
        if ($request->input('action') === 'generate') {
            $temporaryPassword = $this->generateTemporaryPassword();
            $user->password = Hash::make($temporaryPassword);
            $user->save();
            
            return view('admin.users.view-password', [
                'user' => $user,
                'password_verified' => true,
                'temporary_password' => $temporaryPassword,
                'message' => 'A new temporary password has been generated. Copy it now - it will not be shown again!',
            ]);
        }
        
        // Reset with custom password
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return redirect()->route('dashboard.users.index')
                ->with('success', "Password for {$user->username} has been reset successfully.");
        }
        
        // Show password reset form
        return view('admin.users.view-password', [
            'user' => $user,
            'password_verified' => true,
            'message' => 'Password is set. You cannot view the original password (it\'s encrypted), but you can generate a new temporary password or set a custom one below.',
        ]);
    }

    /**
     * Generate a secure temporary password.
     */
    private function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        // Ensure at least one lowercase, one uppercase, one number, one special char
        $password .= 'abcdefghijklmnopqrstuvwxyz'[random_int(0, 25)];
        $password .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, 25)];
        $password .= '0123456789'[random_int(0, 9)];
        $password .= '!@#$%^&*'[random_int(0, 7)];
        
        // Fill the rest randomly
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        // Shuffle to randomize position
        return str_shuffle($password);
    }
}
