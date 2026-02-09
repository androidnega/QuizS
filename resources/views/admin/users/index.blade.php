@extends('layouts.dashboard')

@section('title', 'User management')
@section('dashboard_heading', 'Users')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 font-medium">User management</span>
            </div>
            <button type="button" onclick="openCreateUserModal()" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add user
            </button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned courses</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $u->username }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $u->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $u->role === 'super_admin' ? 'bg-primary-100 text-primary-800' : 'bg-success-100 text-success-800' }}">
                                        {{ $u->role === 'super_admin' ? 'Super Admin' : 'Examiner' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $u->courses->isNotEmpty() ? $u->courses->pluck('name')->join(', ') : '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">••••••</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('dashboard.users.edit', $u) }}" class="text-primary-600 hover:text-primary-900">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No staff users yet. Add an admin or examiner.</td>
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
            <div id="modal_course_field">
                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned courses (for Examiner)</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @php
                        $courses = \App\Models\Course::where('is_archived', false)->orderBy('name')->get();
                    @endphp
                    @foreach($courses as $c)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', [])) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-sm">{{ $c->code ?? $c->name }} — {{ $c->name }}</span>
                        </label>
                    @endforeach
                    @if($courses->isEmpty())
                        <p class="text-sm text-gray-500">No courses yet. Create courses first.</p>
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
    if (role === 'super_admin') {
        courseField.style.display = 'none';
    } else {
        courseField.style.display = 'block';
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
</script>
@endpush
@endsection
