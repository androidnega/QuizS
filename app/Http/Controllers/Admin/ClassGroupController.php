<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClassGroupController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    public function index(): View
    {
        $this->authorize('viewAny', ClassGroup::class);
        $ids = $this->classGroupIds();
        $classGroups = ClassGroup::withCount(['students', 'quizzes', 'courses'])
            ->with('examiner:id,username,name')
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')
            ->paginate(15);
        return view('admin.class-groups.index', compact('classGroups'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        $examiners = $user?->isSuperAdmin()
            ? User::where('role', User::ROLE_EXAMINER)->orderBy('name')->get(['id', 'username', 'name'])
            : null;
        return view('admin.class-groups.create', compact('courses', 'examiners'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $examinerId = $isSuperAdmin
            ? (int) $request->input('examiner_id')
            : $user->id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('examiner_id', $examinerId),
            ],
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];
        if ($isSuperAdmin) {
            $rules['examiner_id'] = ['required', 'integer', 'exists:users,id'];
        }
        $request->validate($rules);

        if ($isSuperAdmin) {
            $examiner = User::find($examinerId);
            if (!$examiner || $examiner->role !== User::ROLE_EXAMINER) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                    ->withInput()
                    ->with('error', 'Selected user must be an examiner.');
            }
        }

        $courseIds = $user->assignedCourseIds();
        $requestCourseIds = array_map('intval', $request->input('course_ids', []));
        foreach ($requestCourseIds as $cid) {
            if (!empty($courseIds) && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                    ->withInput()
                    ->with('error', 'You can only attach courses assigned to you.');
            }
        }

        $classGroup = ClassGroup::create([
            'name' => trim($request->name),
            'examiner_id' => $examinerId,
        ]);
        $classGroup->courses()->sync($requestCourseIds);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Class group created. ' . ($isSuperAdmin ? 'Examiner can add students and create quizzes.' : 'Add students to this class group before creating quizzes.'));
    }

    public function show(ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['courses', 'quizzes', 'examiner:id,username,name']);
        $students = $classGroup->students()->orderBy('index_number')->paginate(20);
        $courseIds = $this->adminUser()?->assignedCourseIds() ?? [];
        $availableCourses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        return view('admin.class-groups.show', compact('classGroup', 'students', 'availableCourses'));
    }

    public function edit(ClassGroup $classGroup): View
    {
        $this->authorize('update', $classGroup);
        $classGroup->load('courses', 'examiner:id,username,name');
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->when(!empty($courseIds), fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('name')
            ->get();
        $examiners = $user?->isSuperAdmin()
            ? User::where('role', User::ROLE_EXAMINER)->orderBy('name')->get(['id', 'username', 'name'])
            : null;
        return view('admin.class-groups.edit', compact('classGroup', 'courses', 'examiners'));
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $user = $this->adminUser();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $examinerId = $isSuperAdmin ? (int) $request->input('examiner_id', $classGroup->examiner_id) : $classGroup->examiner_id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_groups', 'name')->where('examiner_id', $examinerId)->ignore($classGroup->id),
            ],
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];
        if ($isSuperAdmin) {
            $rules['examiner_id'] = ['required', 'integer', 'exists:users,id'];
        }
        $request->validate($rules);

        if ($isSuperAdmin) {
            $examiner = User::find($examinerId);
            if (!$examiner || $examiner->role !== User::ROLE_EXAMINER) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                    ->withInput()
                    ->with('error', 'Selected user must be an examiner.');
            }
        }

        $courseIds = $user ? $user->assignedCourseIds() : [];
        $requestCourseIds = array_map('intval', $request->input('course_ids', []));
        foreach ($requestCourseIds as $cid) {
            if (!empty($courseIds) && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                    ->withInput()
                    ->with('error', 'You can only attach courses assigned to you.');
            }
        }

        $classGroup->update([
            'name' => trim($request->name),
            'examiner_id' => $examinerId,
        ]);
        $classGroup->courses()->sync($requestCourseIds);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)->with('success', 'Class group updated.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('delete', $classGroup);
        if ($classGroup->quizzes()->exists()) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.index')
                ->with('error', 'Cannot delete: this class group has quizzes. Delete or reassign the quizzes first.');
        }
        $name = $classGroup->name;
        $classGroup->delete();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.index')->with('success', "Class group \"{$name}\" deleted.");
    }

    /** Show the student indices management page for this class group. */
    public function studentsIndex(Request $request, ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load('examiner:id,username,name');
        $search = $request->input('search', '');
        $query = $classGroup->students()->with('studentAccount')->orderBy('index_number');
        if ($search !== '') {
            $term = '%' . preg_replace('/%/', '\\%', trim($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('student_name', 'like', $term)
                    ->orWhereHas('studentAccount', function ($q2) use ($term) {
                        $q2->where('phone_contact', 'like', $term);
                    });
            });
        }
        $students = $query->paginate(30)->withQueryString();
        $isSuperAdmin = $this->adminUser()?->isSuperAdmin() ?? false;
        return view('admin.class-groups.students', compact('classGroup', 'students', 'isSuperAdmin', 'search'));
    }

    /** Add a single student to the class group. */
    public function addStudent(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        ClassGroupStudent::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'index_number' => trim($request->index_number),
            ],
            ['student_name' => $request->filled('student_name') ? trim($request->student_name) : null]
        );
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Student index added.');
    }

    /** Show edit form for one student in the class group. */
    public function editStudent(ClassGroup $classGroup, ClassGroupStudent $student): View
    {
        $this->authorize('update', $classGroup);
        if ($student->class_group_id !== $classGroup->id) {
            abort(404);
        }
        return view('admin.class-groups.student-edit', compact('classGroup', 'student'));
    }

    /** Update a student index/name in the class group. */
    public function updateStudent(Request $request, ClassGroup $classGroup, ClassGroupStudent $student): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        if ($student->class_group_id !== $classGroup->id) {
            abort(404);
        }
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;
        // If index changed, ensure no duplicate (unique is class_group_id + index_number)
        if (strcasecmp($student->index_number, $indexNumber) !== 0) {
            if (ClassGroupStudent::where('class_group_id', $classGroup->id)->where('id', '!=', $student->id)->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])->exists()) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                    ->with('error', 'An index with that number already exists in this group.');
            }
        }
        $student->index_number = $indexNumber;
        $student->student_name = $name;
        $student->save();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Student index updated.');
    }

    /** Upload Excel to replace or merge class group students. */
    public function uploadStudents(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'upload_mode' => 'required|in:replace,merge',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }
        $byIndex = [];
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $byIndex[$index] = $name;
        }

        $mode = $request->input('upload_mode');
        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;

        if ($mode === 'replace') {
            $rowsDeleted = $classGroup->students()->count();
            $classGroup->students()->delete();
        }
        foreach ($byIndex as $index => $name) {
            $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)->where('index_number', $index)->first();
            ClassGroupStudent::updateOrCreate(
                ['class_group_id' => $classGroup->id, 'index_number' => $index],
                ['student_name' => $name]
            );
            if ($existing) {
                $rowsUpdated++;
            } else {
                $rowsAdded++;
            }
        }

        AttendanceUploadLog::create([
            'class_group_id' => $classGroup->id,
            'uploaded_by' => $this->adminUser()?->id,
            'upload_mode' => $mode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        $message = $mode === 'replace'
            ? 'Student list replaced with ' . count($byIndex) . ' indices.'
            : 'Merged ' . count($byIndex) . ' indices into the class group.';
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', $message);
    }

    /** Remove a student from the class group. */
    public function destroyStudent(ClassGroup $classGroup, ClassGroupStudent $student): RedirectResponse
    {
        $this->authorize('update', $classGroup);
        if ($student->class_group_id !== $classGroup->id) {
            abort(404);
        }
        $student->delete();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Student index removed.');
    }
}
