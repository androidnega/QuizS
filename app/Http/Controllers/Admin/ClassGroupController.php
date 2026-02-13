<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use App\Exports\ClassGroupStudentsExport;

class ClassGroupController extends Controller
{
    use InteractsWithAdminSession;

    private function classGroupIds(): array
    {
        $user = $this->adminUser();
        return $user ? $user->classGroupIds() : [];
    }

    /**
     * Resolve class group from student record (source of truth for nested URLs).
     * Optionally redirect GET pages to canonical URL when classGroupId in URL is stale.
     */
    private function resolveStudentClassGroup(
        string $classGroupId,
        ClassGroupStudent $student,
        string $ability,
        ?string $canonicalRoute = null
    ): ClassGroup|RedirectResponse {
        $classGroup = $student->classGroup;
        if (! $classGroup) {
            abort(404);
        }
        $this->authorize($ability, $classGroup);

        if ($canonicalRoute && (string) $classGroupId !== (string) $classGroup->getRouteKey()) {
            return redirect()->route($this->staffRoutePrefix() . '.' . $canonicalRoute, [
                'classGroupId' => $classGroup->getRouteKey(),
                'student' => $student->getRouteKey(),
            ]);
        }

        return $classGroup;
    }

    public function index(): View
    {
        $this->authorize('viewAny', ClassGroup::class);
        $ids = $this->classGroupIds();

        // Lecturer-centric: lecturers who have class groups in scope, paginated
        $lecturers = User::where('role', User::ROLE_EXAMINER)
            ->whereHas('classGroups', fn ($q) => $q->whereIn('id', $ids))
            ->withCount(['classGroups'])
            ->with([
                'classGroups' => fn ($q) => $q->whereIn('id', $ids)
                    ->withCount(['students', 'quizzes', 'courses'])
                    ->orderBy('name'),
                'courses' => fn ($q) => $q->where('is_archived', false)->orderBy('name'),
            ])
            ->orderBy('name')
            ->paginate(10, ['id', 'username', 'name']);

        // Unassigned groups (examiner_id is null) in scope
        $unassignedGroups = ClassGroup::withCount(['students', 'quizzes', 'courses'])
            ->whereNull('examiner_id')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        return view('admin.class-groups.index', compact('lecturers', 'unassignedGroups'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassGroup::class);
        $user = $this->adminUser();
        $courseIds = $user?->assignedCourseIds() ?? [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
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
            return redirect()->route('login')->with('error', 'Error');
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
                    ->with('error', 'Error');
            }
        }

        $courseIds = $user->assignedCourseIds();
        $requestCourseIds = array_map('intval', $request->input('course_ids', []));
        foreach ($requestCourseIds as $cid) {
            if (!$user->isSuperAdmin() && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.create')
                    ->withInput()
                    ->with('error', 'Error');
            }
        }

        $classGroup = ClassGroup::create([
            'name' => trim($request->name),
            'examiner_id' => $examinerId,
        ]);
        $classGroup->courses()->sync($requestCourseIds);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)
            ->with('success', 'Saved');
    }

    public function show(ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['courses', 'quizzes', 'examiner:id,username,name']);
        $students = $classGroup->students()->orderBy('index_number')->paginate(20);
        $courseIds = $this->adminUser()?->assignedCourseIds() ?? [];
        $availableCourses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
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
            ->whereIn('id', $courseIds)
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
                    ->with('error', 'Error');
            }
        }

        $courseIds = $user ? $user->assignedCourseIds() : [];
        $requestCourseIds = array_map('intval', $request->input('course_ids', []));
        foreach ($requestCourseIds as $cid) {
            if (!$isSuperAdmin && !in_array($cid, $courseIds, true)) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.edit', $classGroup)
                    ->withInput()
                    ->with('error', 'Error');
            }
        }

        $classGroup->update([
            'name' => trim($request->name),
            'examiner_id' => $examinerId,
        ]);
        $classGroup->courses()->sync($requestCourseIds);

        return redirect()->route($this->staffRoutePrefix() . '.class-groups.show', $classGroup)->with('success', 'Saved');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $this->authorize('delete', $classGroup);
        if ($classGroup->quizzes()->exists()) {
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.index')
                ->with('error', 'Error');
        }
        $name = $classGroup->name;
        $classGroup->delete();
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.index')->with('success', 'Deleted');
    }

    /** Show the student indices management page for this class group. */
    public function studentsIndex(Request $request, ClassGroup $classGroup): View
    {
        $this->authorize('view', $classGroup);
        $classGroup->load('examiner:id,username,name');
        $search = $request->input('search', '');
        // Eager load studentAccount with phone_contact and student_name fields
        $query = $classGroup->students()->with(['studentAccount' => function ($q) {
            $q->select('id', 'index_number', 'phone_contact', 'student_name');
        }])->orderBy('index_number');
        if ($search !== '') {
            $term = '%' . preg_replace('/%/', '\\%', trim($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('student_name', 'like', $term)
                    ->orWhereHas('studentAccount', function ($q2) use ($term) {
                        $q2->where('phone_contact', 'like', $term)
                            ->orWhere('student_name', 'like', $term);
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
        
        $indexNumber = trim($request->index_number);
        $providedName = $request->filled('student_name') ? trim($request->student_name) : null;
        
        // Student is treated as completely new - no data retrieval from previous records
        // They will go through full onboarding (phone, name, etc.)
        
        ClassGroupStudent::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'index_number' => $indexNumber,
            ],
            ['student_name' => $providedName]
        );
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Saved');
    }

    /** Show details page for one student in the class group. */
    public function showStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'view', 'class-groups.students.show');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        
        // Display name priority: student account name > class group name > "—"
        $displayName = $studentAccount?->student_name ?? $student->student_name ?? '—';
        
        // Quiz stats
        $quizzesCount = 0;
        $averageScore = null;
        $lastQuizDate = null;
        
        if ($studentAccount) {
            $sessions = $studentAccount->quizSessions()->with('result')->get();
            $quizzesCount = $sessions->count();
            
            if ($quizzesCount > 0) {
                $scores = $sessions->filter(fn($s) => $s->result)->map(fn($s) => $s->result->score);
                if ($scores->isNotEmpty()) {
                    $averageScore = $scores->average();
                }
                
                $lastSession = $sessions->sortByDesc('created_at')->first();
                $lastQuizDate = $lastSession?->created_at?->format('M j, Y');
            }
        }
        
        return view('admin.class-groups.student-show', compact(
            'classGroup', 
            'student', 
            'studentAccount',
            'phone', 
            'displayName',
            'quizzesCount',
            'averageScore',
            'lastQuizDate'
        ));
    }

    /** Show edit form for one student in the class group. */
    public function editStudent(string $classGroupId, ClassGroupStudent $student): View|RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update', 'class-groups.students.edit');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $student->load('studentAccount');
        $studentAccount = $student->studentAccount;
        $phone = $studentAccount?->phone_contact ?? null;
        
        return view('admin.class-groups.student-edit', compact('classGroup', 'student', 'studentAccount', 'phone'));
    }

    /** Update a student index/name/phone in the class group. */
    public function updateStudent(Request $request, string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
            'phone_contact' => 'nullable|string|max:20',
        ]);
        $indexNumber = trim($request->index_number);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;
        $phoneRaw = $request->filled('phone_contact') ? trim($request->phone_contact) : null;
        $phone = $phoneRaw ? preg_replace('/\D/', '', $phoneRaw) : null;
        $phone = ($phone !== null && $phone !== '') ? $phone : null;
        
        // If index changed, ensure no duplicate (unique is class_group_id + index_number)
        if (strcasecmp($student->index_number, $indexNumber) !== 0) {
            if (ClassGroupStudent::where('class_group_id', $classGroup->id)->where('id', '!=', $student->id)->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])->exists()) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
                    ->with('error', 'Error');
            }
        }
        
        $student->index_number = $indexNumber;
        $student->student_name = $name;
        $student->save();
        
        // Update phone in Student account if exists
        $studentAccount = \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($indexNumber))->first();
        if ($studentAccount && $phone !== null) {
            $otherStudent = \App\Models\Student::where('phone_contact', $phone)->where('id', '!=', $studentAccount->id)->first();
            if ($otherStudent) {
                return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.edit', [$classGroup, $student])
                    ->withInput()
                    ->with('error', 'Error');
            }
            $studentAccount->phone_contact = $phone;
            $studentAccount->save();
        } elseif ($studentAccount && $phone === null) {
            $studentAccount->phone_contact = null;
            $studentAccount->save();
        }
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.show', [$classGroup, $student])->with('success', 'Saved');
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
            
            // Delete ALL data for removed students (complete reset)
            $removedIndices = $classGroup->students()->pluck('index_number');
            foreach ($removedIndices as $removedIndex) {
                $indexUpper = strtoupper(trim($removedIndex));
                
                // Delete ALL quiz sessions (cascades to answers, results, violations)
                \App\Models\QuizSession::whereRaw('UPPER(TRIM(student_index)) = ?', [$indexUpper])->delete();
                
                // Delete ALL quiz acceptances
                \App\Models\QuizAcceptance::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])->delete();
                
                // Delete student accounts (lookup by hash)
                \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($indexUpper))->delete();
                
                // Clear cached OTP data
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $removedIndex);
                \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
            }
            
            $classGroup->students()->delete();
        }
        
        foreach ($byIndex as $index => $name) {
            $indexTrimmed = trim($index);
            
            // Students are treated as completely new - no data retrieval
            // They will go through full onboarding
            
            $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)->where('index_number', $indexTrimmed)->first();
            ClassGroupStudent::updateOrCreate(
                ['class_group_id' => $classGroup->id, 'index_number' => $indexTrimmed],
                ['student_name' => $name ? trim($name) : null]
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

        // Send login tokens by SMS to students with phone numbers; deduct from examiner's SMS balance.
        $classGroup->load('examiner');
        $examiner = $classGroup->examiner;
        $otpTtl = (int) config('quizsnap.otp_ttl_seconds', 14 * 86400);
        $smsSent = 0;
        if ($examiner && $examiner->isExaminer()) {
            $examiner->refresh();
            $remaining = $examiner->sms_remaining;
            if ($remaining > 0) {
                $studentsInGroup = $classGroup->students()->get();
                foreach ($studentsInGroup as $cgStudent) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $indexNumber = strtoupper(trim($cgStudent->index_number));
                    $studentAccount = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
                    if (!$studentAccount || !$studentAccount->hasPhone()) {
                        continue;
                    }
                    $code = (string) random_int(100000, 999999);
                    Cache::put('student_otp:' . $indexNumber, [
                        'code' => $code,
                        'phone' => $studentAccount->phone_contact,
                    ], $otpTtl);
                    $smsMessage = 'Your QuizSnap login code is: ' . $code . '. Valid for 14 days. Do not share.';
                    $result = ArkeselService::sendSms($studentAccount->phone_contact, $smsMessage);
                    if ($result['success']) {
                        $examiner->increment('sms_used');
                        $remaining--;
                        $smsSent++;
                    }
                }
            }
        }
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Saved');
    }

    /** Remove a student from the class group. */
    public function destroyStudent(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        $indexNumber = $student->index_number;
        $indexUpper = strtoupper(trim($indexNumber));
        
        // Delete ALL quiz sessions for this student (across all quizzes)
        // This will cascade delete: answers, results, violations
        \App\Models\QuizSession::whereRaw('UPPER(TRIM(student_index)) = ?', [$indexUpper])->delete();
        
        // Delete ALL quiz acceptances for this student (across all quizzes)
        \App\Models\QuizAcceptance::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])->delete();
        
        // Delete student account (phone, name, etc.) - complete reset; lookup by hash
        \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($indexUpper))->delete();
        
        // Clear any cached OTP data for this student
        \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexNumber);
        \Illuminate\Support\Facades\Cache::forget('student_otp:' . $indexUpper);
        
        // Delete class group student record
        $student->delete();
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)
            ->with('success', 'Deleted');
    }

    /** Remove phone number from a student. */
    public function removeStudentPhone(string $classGroupId, ClassGroupStudent $student): RedirectResponse
    {
        $resolved = $this->resolveStudentClassGroup($classGroupId, $student, 'update');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }
        $classGroup = $resolved;
        
        // Find the Student record by index hash and remove phone
        $studentAccount = \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($student->index_number))->first();
        if ($studentAccount) {
            $studentAccount->phone_contact = null;
            $studentAccount->save();
            return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('success', 'Removed');
        }
        
        return redirect()->route($this->staffRoutePrefix() . '.class-groups.students.index', $classGroup)->with('error', 'Not found');
    }

    /**
     * Export class group students list as Excel.
     */
    public function exportStudentsExcel(ClassGroup $classGroup): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $classGroup);
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new ClassGroupStudentsExport($classGroup), $filename);
    }

    /**
     * Export class group students list as PDF.
     */
    public function exportStudentsPdf(ClassGroup $classGroup): Response
    {
        $this->authorize('view', $classGroup);
        $classGroup->load(['examiner:id,username,name', 'students.studentAccount', 'courses']);
        
        $students = $classGroup->students()
            ->with('studentAccount')
            ->orderBy('index_number')
            ->get();
        
        $lecturer = $classGroup->examiner;
        $lecturerName = $lecturer ? ($lecturer->name ?: $lecturer->username) : '—';
        
        // Get course information (use first course if multiple)
        $courseName = '—';
        $courseCode = '—';
        $courses = $classGroup->courses;
        if ($courses->isNotEmpty()) {
            $firstCourse = $courses->first();
            $courseCode = trim($firstCourse->code ?? '');
            $courseName = trim($firstCourse->name ?? '');
            if ($courseCode && $courseName) {
                $courseName = $courseCode . ' – ' . $courseName;
            } elseif ($courseName) {
                $courseName = $courseName;
            } elseif ($courseCode) {
                $courseName = $courseCode;
            }
        }
        
        $institutionName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_NAME, '');
        $logoPath = \App\Models\Setting::getValue(\App\Models\Setting::KEY_INSTITUTION_LOGO, '');
        $institutionLogoPath = null;
        if ($logoPath) {
            if (str_starts_with($logoPath, 'http')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($logoPath);
                    if ($response->successful()) {
                        $body = $response->body();
                        $mime = $response->header('Content-Type') ?: 'image/png';
                        $institutionLogoPath = 'data:' . (explode(';', $mime)[0] ?: 'image/png') . ';base64,' . base64_encode($body);
                    }
                } catch (\Throwable $e) {
                    // omit logo on fetch failure
                }
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
                if (file_exists($fullPath)) {
                    $mime = @mime_content_type($fullPath) ?: 'image/png';
                    $institutionLogoPath = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        }
        
        $classGroupName = $classGroup->name;
        $reportDate = now()->format('F j, Y');
        
        $pdf = Pdf::loadView('admin.class-groups.export-pdf', [
            'classGroup' => $classGroup,
            'classGroupName' => $classGroupName,
            'students' => $students,
            'lecturerName' => $lecturerName,
            'courseName' => $courseName,
            'reportDate' => $reportDate,
            'institutionName' => $institutionName,
            'institutionLogoPath' => $institutionLogoPath,
        ])->setPaper('a4', 'portrait')->setWarnings(false);
        
        $filename = 'class-list-' . \Illuminate\Support\Str::slug($classGroup->name) . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
