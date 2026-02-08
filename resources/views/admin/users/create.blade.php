@extends('layouts.dashboard')

@section('title', 'Add user')
@section('dashboard_heading', 'Add user')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600">User management</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Add user</span>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Add user</h1>

            <form action="{{ route('dashboard.users.store') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required class="input w-full @error('username') border-danger-500 @enderror">
                    @error('username')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="input w-full @error('name') border-danger-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" required class="input w-full @error('role') border-danger-500 @enderror">
                        <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner</option>
                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    @error('role')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div id="course-field">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assigned courses (for Examiner)</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        @foreach($courses as $c)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', [])) ? 'checked' : '' }}>
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
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required class="input w-full @error('password') border-danger-500 @enderror">
                    @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="input w-full">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary">Create user</button>
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
