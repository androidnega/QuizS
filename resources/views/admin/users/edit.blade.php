@extends('layouts.dashboard')

@section('title', 'Edit user')
@section('admin_heading', 'Edit user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        @if(!isset($isProfileCompletion) || !$isProfileCompletion)
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600">User management</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium min-w-0 truncate">Edit {{ $user->username }}</span>
        </div>
        @endif

        <div class="card p-4 sm:p-6 w-full min-w-0 max-w-full overflow-hidden">
            @if(isset($isProfileCompletion) && $isProfileCompletion)
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Complete Your Profile</h1>
                <p class="text-sm text-gray-600 mb-4 sm:mb-6">Please select your faculty and department to complete your profile.</p>
            @else
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Edit user</h1>
            @endif

            <form action="{{ route('dashboard.users.update', $user) }}" method="post" class="space-y-4 w-full min-w-0">
                @csrf
                @method('PUT')
                @if(!isset($isProfileCompletion) || !$isProfileCompletion)
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required class="input w-full min-w-0 max-w-full @error('username') border-danger-500 @enderror">
                    @error('username')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional, for password reset)</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="input w-full max-w-full min-w-0 @error('email') border-danger-500 @enderror" placeholder="user@example.com">
                    @error('email')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input w-full max-w-full min-w-0 @error('name') border-danger-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" required class="input w-full max-w-full min-w-0 @error('role') border-danger-500 @enderror">
                        <option value="examiner" {{ old('role', $user->role) === 'examiner' ? 'selected' : '' }}>Examiner</option>
                        <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    @error('role')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @else
                {{-- Hidden fields to preserve existing values --}}
                <input type="hidden" name="username" value="{{ $user->username }}">
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <input type="hidden" name="role" value="{{ $user->role }}">
                @endif
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label for="sms_allocation" class="block text-sm font-medium text-gray-700 mb-1">SMS allocation (for Examiner)</label>
                    <input type="number" name="sms_allocation" id="sms_allocation" value="{{ old('sms_allocation', $user->sms_allocation ?? 0) }}" min="0" step="1" class="input w-full max-w-full min-w-0 @error('sms_allocation') border-danger-500 @enderror" placeholder="0">
                    <p class="mt-1 text-xs text-gray-500">Number of SMS the examiner can use to send login tokens to students (e.g. 20).</p>
                    @error('sms_allocation')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div id="institution-field">
                    <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">Institution (for Examiner)</label>
                    <select name="institution_id" id="institution_id" class="input w-full max-w-full min-w-0 @error('institution_id') border-danger-500 @enderror" onchange="loadFaculties()">
                        <option value="">— Select institution —</option>
                        @foreach($institutions ?? [] as $inst)
                            <option value="{{ $inst->id }}" {{ old('institution_id', $user->institution_id) == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                        @endforeach
                    </select>
                    @error('institution_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @elseif($user->isExaminer())
                @if($user->institution_id)
                {{-- Show read-only institution for examiners who have one --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <input type="text" value="{{ $user->institution->display_name ?? 'N/A' }}" class="input w-full max-w-full min-w-0 bg-gray-50" readonly disabled>
                    <input type="hidden" name="institution_id" value="{{ $user->institution_id }}">
                </div>
                @elseif(isset($isProfileCompletion) && $isProfileCompletion)
                {{-- Show institution selector if missing in profile completion mode --}}
                <div id="institution-field">
                    <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">Institution <span class="text-red-500">*</span></label>
                    <select name="institution_id" id="institution_id" required class="input w-full max-w-full min-w-0 @error('institution_id') border-danger-500 @enderror" onchange="loadFaculties()">
                        <option value="">— Select institution —</option>
                        @foreach($institutions ?? [] as $inst)
                            <option value="{{ $inst->id }}" {{ old('institution_id', $user->institution_id) == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                        @endforeach
                    </select>
                    @error('institution_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @endif
                @endif
                @if($user->isExaminer())
                @if(!$user->faculty_id || (isset($isProfileCompletion) && $isProfileCompletion))
                <div id="faculty-field">
                    <label for="faculty_id" class="block text-sm font-medium text-gray-700 mb-1">Faculty <span class="text-red-500">*</span></label>
                    <select name="faculty_id" id="faculty_id" class="input w-full max-w-full min-w-0 @error('faculty_id') border-danger-500 @enderror" onchange="loadDepartments()" {{ (isset($isProfileCompletion) && $isProfileCompletion && !$user->faculty_id) ? 'required' : '' }}>
                        <option value="">— Select faculty —</option>
                        @foreach($faculties ?? [] as $faculty)
                            <option value="{{ $faculty->id }}" {{ old('faculty_id', $user->faculty_id) == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                    @error('faculty_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @endif
                @if(!$user->department_id || (isset($isProfileCompletion) && $isProfileCompletion))
                <div id="department-field">
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span></label>
                    <select name="department_id" id="department_id" class="input w-full max-w-full min-w-0 @error('department_id') border-danger-500 @enderror" {{ (isset($isProfileCompletion) && $isProfileCompletion && !$user->department_id) ? 'required' : '' }}>
                        <option value="">— Select department —</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @endif
                @endif
                @if(!isset($isProfileCompletion) || !$isProfileCompletion)
                <div id="course-field" class="min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assigned courses (for Examiner)</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 min-w-0">
                        @foreach($courses as $c)
                            <label class="flex items-start sm:items-center gap-2 min-w-0">
                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', $user->courses->pluck('id')->all())) ? 'checked' : '' }} class="mt-0.5 shrink-0">
                                <span class="text-sm break-words">{{ $c->code ?? $c->name }} — {{ $c->name }}</span>
                            </label>
                        @endforeach
                        @if($courses->isEmpty())
                            <p class="text-sm text-gray-500">No courses yet.</p>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password (leave blank to keep current)</label>
                    <input type="password" name="password" id="password" class="input w-full max-w-full min-w-0 @error('password') border-danger-500 @enderror" placeholder="Set or reset password for this user" minlength="8" autocomplete="new-password">
                    <p class="mt-1 text-xs text-gray-500">At least 8 characters, including one letter and one number.</p>
                    @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input w-full max-w-full min-w-0">
                </div>
                @endif
                @endif
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn btn-primary shrink-0">
                        @if(isset($isProfileCompletion) && $isProfileCompletion)
                            Complete Profile
                        @else
                            Update user
                        @endif
                    </button>
                    @if(isset($isProfileCompletion) && $isProfileCompletion)
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary shrink-0">Cancel</a>
                    @else
                        <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary shrink-0">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const currentInstitutionId = {{ $user->institution_id ?? 'null' }};
const currentFacultyId = {{ $user->faculty_id ?? 'null' }};
const currentDepartmentId = {{ $user->department_id ?? 'null' }};

function loadFaculties() {
    const institutionId = document.getElementById('institution_id').value;
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    
    // Clear options
    facultySelect.innerHTML = '<option value="">— Select faculty —</option>';
    departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    
    if (!institutionId) {
        return;
    }
    
    // Fetch faculties for this institution
    fetch(`{{ route('dashboard.faculties.by-institution', '') }}/${institutionId}`, {
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.faculties) {
                data.faculties.forEach(faculty => {
                    const option = document.createElement('option');
                    option.value = faculty.id;
                    option.textContent = faculty.name;
                    if (currentInstitutionId == institutionId && faculty.id == currentFacultyId) {
                        option.selected = true;
                        // Load departments for selected faculty
                        setTimeout(() => loadDepartments(), 100);
                    }
                    facultySelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading faculties:', error));
}

function loadDepartments() {
    const facultyId = document.getElementById('faculty_id').value;
    const departmentSelect = document.getElementById('department_id');
    
    // Clear options
    departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    
    if (!facultyId) {
        return;
    }
    
    // Fetch departments for this faculty
    fetch(`{{ route('dashboard.departments.by-faculty', '') }}/${facultyId}`, {
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.departments) {
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    if (currentFacultyId == facultyId && dept.id == currentDepartmentId) {
                        option.selected = true;
                    }
                    departmentSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

// Load faculties on page load if institution is selected
@if($user->institution_id)
    loadFaculties();
@endif

// Load departments on page load if faculty is selected
@if($user->faculty_id)
    setTimeout(() => loadDepartments(), 200);
@endif
</script>
@endpush
@endsection
