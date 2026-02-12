@extends('layouts.dashboard')

@section('title', 'User management')
@section('dashboard_heading', 'Users')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 shrink-0">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 font-medium">User management</span>
            </div>
            @if(isset($isSuperAdmin) && $isSuperAdmin)
            <button type="button" onclick="openCreateUserModal()" class="btn btn-primary shrink-0">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add user
            </button>
            @endif
        </div>

        <div class="card overflow-hidden min-w-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-28">Username</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-36">Institution</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[120px] max-w-[200px]">Courses</th>
                            @if(isset($isSuperAdmin) && $isSuperAdmin)
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">SMS</th>
                            @endif
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 break-words" title="{{ $u->username }}">{{ $u->username }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words" title="{{ $u->name ?? '-' }}">{{ $u->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $u->role === 'super_admin' ? 'bg-primary-100 text-primary-800' : 'bg-success-100 text-success-800' }}">
                                        {{ $u->role === 'super_admin' ? 'Super Admin' : 'Examiner' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words" title="{{ $u->institution?->name ?? '—' }}">{{ $u->institution?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600">
                                    <div class="flex flex-wrap gap-1.5 min-w-0">
                                        @if($u->courses->isNotEmpty())
                                            @foreach($u->courses->take(3) as $course)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 truncate max-w-[140px]" title="{{ $course->name }}">
                                                    {{ $course->name }}
                                                </span>
                                            @endforeach
                                            @if($u->courses->count() > 3)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                    +{{ $u->courses->count() - 3 }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                @if(isset($isSuperAdmin) && $isSuperAdmin)
                                <td class="px-3 py-2 text-sm">
                                    @if($u->role === 'examiner')
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600" id="sms-display-{{ $u->id }}">
                                            {{ $u->sms_remaining ?? 0 }} / {{ $u->sms_allocation ?? 0 }}
                                        </span>
                                        <button type="button" onclick="openSmsModal({{ $u->id }}, '{{ $u->username }}', {{ $u->sms_allocation ?? 0 }}, {{ $u->sms_used ?? 0 }})" class="inline-flex p-1 rounded text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Set SMS allocation">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-3 py-2 text-right text-sm">
                                    <div class="flex justify-end gap-1">
                                        @if(isset($isSuperAdmin) && $isSuperAdmin)
                                            <a href="{{ route('dashboard.users.view-password-form', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Reset password">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            </a>
                                            <form action="{{ route('dashboard.users.revoke', $u) }}" method="post" class="inline" onsubmit="return confirm('Revoke access? User will need to log in again.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-colors" title="Revoke access">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            @if($u->role === 'examiner')
                                            <form action="{{ route('dashboard.users.destroy', $u) }}" method="post" class="inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-danger-600 hover:bg-danger-50 transition-colors" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                            @endif
                                        @else
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($isSuperAdmin) && $isSuperAdmin ? '7' : '6' }}" class="px-3 py-12 text-center text-gray-500">No staff users yet. Add an admin or examiner.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h2 class="text-xl font-bold text-gray-900">Add user</h2>
            <button type="button" onclick="closeCreateUserModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form action="{{ route('dashboard.users.store') }}" method="post" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="modal_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="modal_username" value="{{ old('username') }}" required class="input w-full @error('username') border-danger-500 @enderror">
                @error('username')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="modal_email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional, for password reset)</label>
                <input type="email" name="email" id="modal_email" value="{{ old('email') }}" class="input w-full @error('email') border-danger-500 @enderror" placeholder="user@example.com">
                @error('email')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="modal_name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                <input type="text" name="name" id="modal_name" value="{{ old('name') }}" class="input w-full @error('name') border-danger-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="modal_role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="modal_role" required class="input w-full @error('role') border-danger-500 @enderror" onchange="toggleCourseField()">
                    <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div id="modal_institution_field">
                <label for="modal_institution_id" class="block text-sm font-medium text-gray-700 mb-1">Institution (for Examiner)</label>
                <select name="institution_id" id="modal_institution_id" class="input w-full @error('institution_id') border-danger-500 @enderror">
                    <option value="">— Select institution —</option>
                    @foreach($institutions ?? [] as $inst)
                        <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                    @endforeach
                </select>
                @error('institution_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div id="modal_course_field">
                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned courses (for Examiner)</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @if(isset($courses) && $courses->isNotEmpty())
                        @foreach($courses as $c)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', [])) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm">{{ $c->code ?? $c->name }} — {{ $c->name }}</span>
                            </label>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500">No courses available. {{ isset($isSuperAdmin) && $isSuperAdmin ? 'Create courses first.' : 'Contact the administrator to assign courses.' }}</p>
                    @endif
                </div>
                @error('course_ids')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="modal_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="modal_password" required class="input w-full @error('password') border-danger-500 @enderror" minlength="8" autocomplete="new-password">
                <p class="mt-1 text-xs text-gray-500">At least 8 characters, including one letter and one number.</p>
                @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="modal_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" id="modal_password_confirmation" required class="input w-full">
            </div>
            
            <!-- Modal Footer -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="btn btn-primary flex-1 sm:flex-none">Create user</button>
                <button type="button" onclick="closeCreateUserModal()" class="btn btn-secondary flex-1 sm:flex-none">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('hidden');
    document.getElementById('createUserModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
    toggleCourseField();
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
    document.getElementById('createUserModal').classList.remove('flex');
    document.body.style.overflow = '';
    // Reset form if no errors
    @if(!$errors->any())
    const form = document.querySelector('#createUserModal form');
    if (form) form.reset();
    @endif
}

function toggleCourseField() {
    const role = document.getElementById('modal_role').value;
    const courseField = document.getElementById('modal_course_field');
    const institutionField = document.getElementById('modal_institution_field');
    if (role === 'super_admin') {
        courseField.style.display = 'none';
        if (institutionField) institutionField.style.display = 'none';
    } else {
        courseField.style.display = 'block';
        if (institutionField) institutionField.style.display = 'block';
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateUserModal();
    }
});

// Close modal on backdrop click
document.getElementById('createUserModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateUserModal();
    }
});

// Auto-open modal if there are validation errors
@if($errors->any() && old('_token'))
    openCreateUserModal();
@endif

// SMS Allocation Modal
function openSmsModal(userId, username, currentAllocation, currentUsed) {
    const modal = document.getElementById('smsModal');
    const form = document.getElementById('smsForm');
    const display = document.getElementById('sms-display-' + userId);
    
    document.getElementById('smsUserId').value = userId;
    document.getElementById('smsUsername').textContent = username;
    document.getElementById('smsCurrent').textContent = currentAllocation;
    document.getElementById('smsUsed').textContent = currentUsed;
    document.getElementById('smsRemaining').textContent = Math.max(0, currentAllocation - currentUsed);
    document.getElementById('smsAllocationInput').value = currentAllocation;
    document.getElementById('smsError').classList.add('hidden');
    document.getElementById('smsError').textContent = '';
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    document.getElementById('smsAllocationInput').focus();
}

function closeSmsModal() {
    const modal = document.getElementById('smsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.getElementById('smsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const userId = document.getElementById('smsUserId').value;
    const allocation = parseInt(document.getElementById('smsAllocationInput').value) || 0;
    const errorEl = document.getElementById('smsError');
    const submitBtn = document.getElementById('smsSubmitBtn');
    
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        const response = await fetch('{{ route("dashboard.users.update-sms") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ user_id: userId, sms_allocation: allocation })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update display
            const display = document.getElementById('sms-display-' + userId);
            display.textContent = data.remaining + ' / ' + data.allocation;
            closeSmsModal();
            
            // Show success message (you could add a toast notification here)
            if (window.showToast) {
                showToast('SMS allocation updated successfully', 'success');
            }
        } else {
            errorEl.textContent = data.message || 'Failed to update SMS allocation';
            errorEl.classList.remove('hidden');
        }
    } catch (error) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update';
    }
});

// Close SMS modal on escape or backdrop click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSmsModal();
    }
});

document.getElementById('smsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSmsModal();
    }
});
</script>
@endpush

<!-- SMS Allocation Modal -->
<div id="smsModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Set SMS Allocation</h2>
            <button type="button" onclick="closeSmsModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="smsForm" class="p-6 space-y-4">
            <input type="hidden" id="smsUserId" name="user_id">
            <div>
                <p class="text-sm text-gray-600 mb-4">
                    Examiner: <strong id="smsUsername"></strong><br>
                    Current: <span id="smsCurrent" class="font-semibold">0</span> | 
                    Used: <span id="smsUsed" class="font-semibold">0</span> | 
                    Remaining: <span id="smsRemaining" class="font-semibold text-green-600">0</span>
                </p>
            </div>
            <div>
                <label for="smsAllocationInput" class="block text-sm font-medium text-gray-700 mb-1">SMS Allocation</label>
                <input type="number" id="smsAllocationInput" name="sms_allocation" min="0" step="1" required class="input w-full" placeholder="e.g. 20, 50, 100">
                <p class="mt-1 text-xs text-gray-500">Number of SMS credits this examiner can use to send login tokens.</p>
            </div>
            <div id="smsError" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" id="smsSubmitBtn" class="btn btn-primary flex-1">Update</button>
                <button type="button" onclick="closeSmsModal()" class="btn btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
