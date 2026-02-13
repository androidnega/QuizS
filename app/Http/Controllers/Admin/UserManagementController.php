<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Faculty;
use App\Models\Department;
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
        
        $users = $query->with('institution')
            ->orderBy('role')
            ->orderBy('username')
            ->paginate(20);
        
        // Get courses for the create modal (only courses examiner has access to)
        $courseIds = $user ? $user->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        $institutions = Institution::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'isSuperAdmin', 'courses', 'institutions'));
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
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        $institutions = Institution::orderBy('name')->get();
        return view('admin.users.create', compact('courses', 'institutions', 'isSuperAdmin'));
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
        
        // Only Super Admin can assign courses, institution, and SMS allocation
        if ($isSuperAdmin) {
            $rules['course_ids'] = 'nullable|array';
            $rules['course_ids.*'] = 'exists:courses,id';
            $rules['institution_id'] = 'nullable|exists:institutions,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
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
        if ($isSuperAdmin && $request->filled('institution_id')) {
            $attrs['institution_id'] = $request->institution_id;
        }
        if ($isSuperAdmin && $request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
            $attrs['sms_allocation'] = max(0, (int) $request->sms_allocation);
        }
        $newUser = User::create($attrs);
        if ($newUser->isExaminer() && $request->filled('course_ids')) {
            $newUser->courses()->sync($request->input('course_ids', []));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Saved');
    }

    public function edit(Request $request, User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }
        
        // Examiners can only edit themselves
        if (!$isSuperAdmin && $currentUser && $currentUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        
        $user->load(['courses', 'institution', 'faculty', 'department']);
        $courseIds = $currentUser ? $currentUser->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        $institutions = Institution::orderBy('name')->get();
        
        // Load faculties and departments for examiner (both super admin and examiner can see)
        $faculties = collect();
        $departments = collect();
        $institutionIdForFaculties = $request->get('institution_id', $user->institution_id);
        if ($institutionIdForFaculties) {
            $faculties = Faculty::where('institution_id', $institutionIdForFaculties)->orderBy('name')->get();
            $facultyIdForDepartments = $request->get('faculty_id', $user->faculty_id);
            if ($facultyIdForDepartments) {
                $departments = Department::where('faculty_id', $facultyIdForDepartments)->orderBy('name')->get();
            }
        }
        
        // Check if this is a profile completion flow (examiner editing themselves and missing faculty/department)
        $isProfileCompletion = $request->has('complete_profile') && 
                               !$isSuperAdmin && 
                               $currentUser && 
                               $currentUser->id === $user->id &&
                               (!$user->faculty_id || !$user->department_id);
        
        return view('admin.users.edit', compact('user', 'courses', 'institutions', 'faculties', 'departments', 'isSuperAdmin', 'isProfileCompletion'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
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
        
        // Only Super Admin can assign courses, institution, and SMS allocation
        if ($isSuperAdmin) {
            $rules['course_ids'] = 'nullable|array';
            $rules['course_ids.*'] = 'exists:courses,id';
            $rules['institution_id'] = 'nullable|exists:institutions,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
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
        
        // Only Super Admin can change roles - examiners keep their existing role
        if ($isSuperAdmin && $request->has('role')) {
            $user->role = $request->role;
        }
        // If examiner is updating, role is preserved via hidden input and not changed
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($isSuperAdmin) {
            $user->institution_id = $request->filled('institution_id') ? $request->institution_id : null;
            if ($request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
                $user->sms_allocation = max(0, (int) $request->sms_allocation);
            }
        }
        
        // Handle faculty and department (both Super Admin and Examiners can set)
        if ($request->filled('faculty_id')) {
            $user->faculty_id = $request->faculty_id;
            // Reset department if faculty changes and new department doesn't belong to new faculty
            if ($request->filled('department_id')) {
                $department = Department::find($request->department_id);
                if ($department && $department->faculty_id == $request->faculty_id) {
                    $user->department_id = $request->department_id;
                } else {
                    $user->department_id = null;
                }
            } else {
                $user->department_id = null;
            }
        } elseif ($request->has('faculty_id') && $request->faculty_id === '') {
            $user->faculty_id = null;
            $user->department_id = null;
        }
        
        if ($request->filled('department_id') && $user->faculty_id) {
            $department = Department::find($request->department_id);
            if ($department && $department->faculty_id == $user->faculty_id) {
                $user->department_id = $request->department_id;
            }
        } elseif ($request->has('department_id') && $request->department_id === '') {
            $user->department_id = null;
        }
        
        $user->save();

        if ($isSuperAdmin && $user->isExaminer() && $request->filled('course_ids')) {
            $user->courses()->sync($request->input('course_ids', []));
        }

        // If examiner is updating their own profile, redirect to profile page
        if (!$isSuperAdmin && $currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.profile.show')
                ->with('success', 'Saved');
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Saved');
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
                ->with('error', 'Invalid password');
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
                ->with('success', 'Reset');
        }
        
        // Show password reset form
        return view('admin.users.view-password', [
            'user' => $user,
            'password_verified' => true,
            'message' => 'Password is set. You cannot view the original password (it\'s encrypted), but you can generate a new temporary password or set a custom one below.',
        ]);
    }

    /**
     * Reset examiner password directly (without admin password verification).
     * Generates a temporary password and shows it to the admin.
     */
    public function resetPassword(User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can reset passwords
        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can reset user passwords.');
        }
        
        // Only allow resetting passwords for examiners
        if (!$user->isExaminer()) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }
        
        // Generate a temporary password
        $temporaryPassword = $this->generateTemporaryPassword();
        $user->password = Hash::make($temporaryPassword);
        $user->save();
        
        // Revoke existing sessions
        $user->remember_token = null;
        $user->save();
        
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
        
        return redirect()->route('dashboard.users.index')
            ->with('success', 'Reset')
            ->with('temp_password', $temporaryPassword)
            ->with('reset_user_id', $user->id);
    }

    /**
     * Revoke user access: clear remember_token and sessions so they must log in again.
     */
    public function revoke(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can revoke user access.');
        }

        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }

        $user->remember_token = null;
        $user->save();

        // Delete sessions for this user (Laravel database driver)
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Revoked');
    }

    /**
     * Delete an examiner user. Cannot delete super admins or yourself.
     */
    public function destroy(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            abort(403, 'Only Super Administrators can delete users.');
        }

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }

        if ($currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Error');
        }

        $user->courses()->detach();
        $user->delete();

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Deleted');
    }

    /**
     * Update SMS allocation for an examiner (AJAX).
     */
    public function updateSms(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Administrators can set SMS allocation.',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'sms_allocation' => 'required|integer|min:0',
        ]);

        $user = User::findOrFail($request->user_id);
        
        if ($user->role !== User::ROLE_EXAMINER) {
            return response()->json([
                'success' => false,
                'message' => 'SMS allocation can only be set for examiners.',
            ], 422);
        }

        $user->sms_allocation = max(0, (int) $request->sms_allocation);
        $user->save();

        return response()->json([
            'success' => true,
            'allocation' => $user->sms_allocation,
            'used' => $user->sms_used ?? 0,
            'remaining' => $user->sms_remaining,
            'message' => 'SMS allocation updated successfully.',
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
