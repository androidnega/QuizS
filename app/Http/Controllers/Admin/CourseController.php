<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Course management: Super Admin always, Examiner when setting allows.
 * Create, edit course code/title, assign examiners, archive.
 */
class CourseController extends Controller
{
    use InteractsWithAdminSession;
    public function index(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // Lecturer-centric: lecturers who have courses assigned, paginated
        $lecturers = User::where('role', User::ROLE_EXAMINER)
            ->whereHas('courses')
            ->when(!$isSuperAdmin && $user?->isExaminer(), fn ($q) => $q->where('id', $user->id))
            ->withCount('courses')
            ->with([
                'courses' => fn ($q) => $q->where('is_archived', false)
                    ->withCount(['quizzes', 'validIndices'])
                    ->with('examiners:id,username,name')
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->paginate(10, ['id', 'username', 'name']);

        // Courses with no examiners (unassigned)
        $unassignedQuery = Course::withCount(['quizzes', 'validIndices'])
            ->with('examiners:id,username,name')
            ->whereDoesntHave('examiners')
            ->where('is_archived', false);
        if (!$isSuperAdmin && $user?->isExaminer()) {
            $unassignedQuery->whereRaw('1=0'); // Examiners never see unassigned
        }
        $unassignedCourses = $unassignedQuery->orderBy('name')->get();

        return view('admin.courses.index', compact('lecturers', 'unassignedCourses', 'isSuperAdmin'));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Super Admin can assign any examiners, Examiner can only assign themselves
        $examiners = $isSuperAdmin 
            ? User::where('role', User::ROLE_EXAMINER)->orderBy('username')->get()
            : ($user && $user->isExaminer() ? collect([$user]) : collect());
        
        return view('admin.courses.create', compact('examiners', 'isSuperAdmin'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        $rules = [
            'code' => 'required|string|max:64|unique:courses,code',
            'name' => 'required|string|max:255',
        ];
        
        // Only Super Admin can assign multiple examiners
        if ($isSuperAdmin) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'exists:users,id';
        }
        
        $request->validate($rules);
        
        $course = Course::create([
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)), // Force uppercase
            'is_archived' => false,
        ]);
        
        // Super Admin can assign examiners, Examiner automatically assigned to their own course
        if ($isSuperAdmin) {
            $course->examiners()->sync($request->input('examiner_ids', []));
        } elseif ($user && $user->isExaminer()) {
            $course->examiners()->sync([$user->id]);
        }
        
        return redirect()->route('dashboard.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Examiners can only edit courses they're assigned to
        if (!$isSuperAdmin && $user && $user->isExaminer()) {
            if (!$course->examiners->contains($user->id)) {
                return redirect()->route('dashboard.courses.index')
                    ->with('error', 'You can only edit courses assigned to you.');
            }
        }
        
        $course->load('examiners:id,username,name');
        
        // Super Admin can assign any examiners, Examiner can only see themselves
        $examiners = $isSuperAdmin 
            ? User::where('role', User::ROLE_EXAMINER)->orderBy('username')->get()
            : ($user && $user->isExaminer() ? collect([$user]) : collect());
        
        return view('admin.courses.edit', compact('course', 'examiners', 'isSuperAdmin'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Examiners can only edit courses they're assigned to
        if (!$isSuperAdmin && $user && $user->isExaminer()) {
            if (!$course->examiners->contains($user->id)) {
                return redirect()->route('dashboard.courses.index')
                    ->with('error', 'You can only edit courses assigned to you.');
            }
        }
        
        $rules = [
            'code' => 'required|string|max:64|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
        ];
        
        // Only Super Admin can assign multiple examiners
        if ($isSuperAdmin) {
            $rules['examiner_ids'] = 'nullable|array';
            $rules['examiner_ids.*'] = 'exists:users,id';
        }
        
        $request->validate($rules);
        
        $course->update([
            'code' => trim($request->code),
            'name' => strtoupper(trim($request->name)), // Force uppercase
        ]);
        
        // Super Admin can assign examiners, Examiner keeps themselves assigned
        if ($isSuperAdmin) {
            $course->examiners()->sync($request->input('examiner_ids', []));
        } elseif ($user && $user->isExaminer()) {
            // Ensure examiner remains assigned to their course
            if (!$course->examiners->contains($user->id)) {
                $course->examiners()->attach($user->id);
            }
        }
        
        return redirect()->route('dashboard.courses.index')->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Examiners can only archive courses they're assigned to
        if (!$isSuperAdmin && $user && $user->isExaminer()) {
            if (!$course->examiners->contains($user->id)) {
                return redirect()->route('dashboard.courses.index')
                    ->with('error', 'You can only archive courses assigned to you.');
            }
        }
        
        $course->update(['is_archived' => true]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course archived.');
    }

    public function unarchive(Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Examiners can only unarchive courses they're assigned to
        if (!$isSuperAdmin && $user && $user->isExaminer()) {
            if (!$course->examiners->contains($user->id)) {
                return redirect()->route('dashboard.courses.index')
                    ->with('error', 'You can only restore courses assigned to you.');
            }
        }
        
        $course->update(['is_archived' => false]);
        return redirect()->route('dashboard.courses.index')->with('success', 'Course restored.');
    }

    /**
     * Permanently delete a course. Super Admin only. Blocked if course has quizzes.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $user = $this->adminUser();
        
        // Only Super Admin can delete courses
        if (!$user || !$user->isSuperAdmin()) {
            return redirect()->route('dashboard.courses.index')
                ->with('error', 'Only Super Administrators can delete courses.');
        }
        
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
