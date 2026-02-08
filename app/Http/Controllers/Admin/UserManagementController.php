<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER])
            ->with('courses')
            ->orderBy('role')
            ->orderBy('username')
            ->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $courses = Course::where('is_archived', false)->orderBy('name')->get();
        return view('admin.users.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'name' => 'nullable|string|max:255',
            'role' => 'required|in:super_admin,examiner',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name ?: $request->username,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);
        if ($user->isExaminer()) {
            $user->courses()->sync($request->input('course_ids', []));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot edit this user.');
        }
        $user->load('courses');
        $courses = Course::where('is_archived', false)->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'courses'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER], true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot edit this user.');
        }

        $rules = [
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'name' => 'nullable|string|max:255',
            'role' => 'required|in:super_admin,examiner',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];
        // Only Super Admin can have password changed here; Examiner must change own password in profile.
        if ($user->role === User::ROLE_SUPER_ADMIN && $request->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }
        $request->validate($rules);

        $user->username = $request->username;
        $user->name = $request->name ?: $user->username;
        $user->role = $request->role;
        if ($user->role === User::ROLE_SUPER_ADMIN && $request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        if ($user->isExaminer()) {
            $user->courses()->sync($request->input('course_ids', []));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'User updated.');
    }
}
