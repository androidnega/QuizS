@extends('layouts.dashboard')

@section('title', 'Edit user')
@section('admin_heading', 'Edit user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600">User management</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium min-w-0 truncate">Edit {{ $user->username }}</span>
        </div>

        <div class="card p-4 sm:p-6 w-full min-w-0 max-w-full overflow-hidden">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Edit user</h1>

            <form action="{{ route('dashboard.users.update', $user) }}" method="post" class="space-y-4 w-full min-w-0">
                @csrf
                @method('PUT')
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
                @endif
                @if($user->isExaminer() || (auth()->user()->isSuperAdmin() && $user->isExaminer()))
                <div id="faculty-field">
                    <label for="faculty_id" class="block text-sm font-medium text-gray-700 mb-1">Faculty</label>
                    <select name="faculty_id" id="faculty_id" class="input w-full max-w-full min-w-0 @error('faculty_id') border-danger-500 @enderror" onchange="loadDepartments()">
                        <option value="">— Select faculty —</option>
                        @foreach($faculties ?? [] as $faculty)
                            <option value="{{ $faculty->id }}" {{ old('faculty_id', $user->faculty_id) == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                    @error('faculty_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div id="department-field">
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" id="department_id" class="input w-full max-w-full min-w-0 @error('department_id') border-danger-500 @enderror">
                        <option value="">— Select department —</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @endif
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
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn btn-primary shrink-0">Update user</button>
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary shrink-0">Cancel</a>
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
    fetch(`/dashboard/institutions/${institutionId}/edit`)
        .then(response => response.text())
        .then(html => {
            // Parse faculties from the institution edit page or use API
            // For now, we'll reload the page with the new institution
            // In a real implementation, you'd have an API endpoint
            if (currentInstitutionId != institutionId) {
                // Reset faculty and department
                facultySelect.value = '';
                departmentSelect.value = '';
            }
        })
        .catch(error => console.error('Error loading faculties:', error));
    
    // Load faculties via AJAX (we'll need to add an endpoint or use existing data)
    // For now, reload page to get fresh data
    if (currentInstitutionId != institutionId) {
        window.location.href = `{{ route('dashboard.users.edit', $user) }}?institution_id=${institutionId}`;
    }
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
                    if (currentFacultyId == facultyId && dept.id == {{ $user->department_id ?? 'null' }}) {
                        option.selected = true;
                    }
                    departmentSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading departments:', error));
}

// Load departments on page load if faculty is selected
@if($user->faculty_id)
    loadDepartments();
@endif
</script>
@endpush
@endsection
