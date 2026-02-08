<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Course management: Super Admin only.
 * Create, edit course code/title, assign examiners, archive.
 */
class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::withCount(['quizzes', 'validIndices'])
            ->with('examiners:id,username,name')
            ->orderBy('name')
            ->get();
        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $examiners = User::where('role', User::ROLE_EXAMINER)->orderBy('username')->get();
        return view('admin.courses.create', compact('examiners'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:64|unique:courses,code',
            'name' => 'required|string|max:255',
            'examiner_ids' => 'nullable|array',
            'examiner_ids.*' => 'exists:users,id',
        ]);
        $course = Course::create([
            'code' => trim($request->code),
            'name' => trim($request->name),
            'is_archived' => false,
        ]);
        $course->examiners()->sync($request->input('examiner_ids', []));
        return redirect()->route('dashboard.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $course->load('examiners:id,username,name');
        $examiners = User::where('role', User::ROLE_EXAMINER)->orderBy('username')->get();
        return view('admin.courses.edit', compact('course', 'examiners'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:64|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
            'examiner_ids' => 'nullable|array',
            'examiner_ids.*' => 'exists:users,id',
        ]);
        $course->update([
            'code' => trim($request->code),
            'name' => trim($request->name),
        ]);
        $course->examiners()->sync($request->input('examiner_ids', []));
        return redirect()->route('dashboard.courses.index')->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $course->update(['is_archived' => true]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course archived.');
    }

    public function unarchive(Course $course): RedirectResponse
    {
        $course->update(['is_archived' => false]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course restored.');
    }

    /**
     * Permanently delete a course. Super Admin only. Blocked if course has quizzes.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->quizzes()->exists()) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Cannot delete: this course has quizzes. Archive the course or remove/reassign the quizzes first.');
        }
        $name = $course->name;
        $course->examiners()->detach();
        $course->classGroups()->detach();
        $course->validIndices()->delete();
        $course->delete();
        return redirect()->route('dashboard.courses.index')->with('success', "Course \"{$name}\" deleted.");
    }
}
