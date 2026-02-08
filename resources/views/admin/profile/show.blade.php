@extends('layouts.dashboard')

@section('title', 'Profile')
@section('dashboard_heading', 'Profile')

@section('dashboard_content')
<div class="w-full">
    @if(session('success'))
        <div class="alert alert-success text-sm py-2 mb-3">{{ session('success') }}</div>
    @endif

    @if(!$user)
        <div class="rounded-lg border border-gray-200 bg-white p-3 text-center">
            <p class="text-xs text-gray-600">Session expired. <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-800">Sign in again</a>.</p>
        </div>
    @else
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Left: Lecturer info --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 min-w-0">
            <h2 class="text-xs font-semibold text-gray-800 mb-2">Lecturer info</h2>
            <dl class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 text-xs">
                <div><dt class="text-gray-500">Username</dt><dd class="font-medium text-gray-900">{{ $user->username ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Name</dt><dd class="font-medium text-gray-900">{{ $user->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Role</dt><dd class="font-medium text-gray-900">{{ $user->role ? ucfirst(str_replace('_', ' ', $user->role)) : '—' }}</dd></div>
                @if(isset($user->course) && $user->course)
                <div><dt class="text-gray-500">Course</dt><dd class="font-medium text-gray-900">{{ $user->course->name }}</dd></div>
                @endif
            </dl>
            <form action="{{ route('dashboard.profile.update') }}" method="post" class="mt-3 space-y-2">
                @csrf
                @method('PUT')
                <div>
                    <label for="username" class="block text-xs font-medium text-gray-700 mb-0.5">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="input text-sm py-1.5 min-h-0 w-full" required>
                    @error('username')<p class="mt-0.5 text-xs text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-xs font-medium text-gray-700 mb-0.5">Display name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input text-sm py-1.5 min-h-0 w-full">
                    @error('name')<p class="mt-0.5 text-xs text-danger-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary text-sm py-1.5 px-3">Save profile</button>
            </form>
        </div>

        {{-- Right: Profile photo + Security --}}
        <div class="flex flex-col gap-4 min-w-0">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="text-xs font-semibold text-gray-800 mb-2">Profile photo</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @if($user->avatar_url ?? null)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-12 w-12 rounded-full object-cover border border-gray-200 flex-shrink-0" />
                    @else
                        <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-600">{{ strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1)) }}</span>
                    @endif
                    <form action="{{ route('dashboard.profile.avatar') }}" method="post" enctype="multipart/form-data" class="flex flex-col gap-1 min-w-0">
                        @csrf
                        @method('PUT')
                        <input type="file" name="avatar" accept="image/*" required class="text-xs file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                        @error('avatar')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-secondary text-xs py-1 px-2.5">Upload photo</button>
                    </form>
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="text-xs font-semibold text-gray-800 mb-1">Security</h2>
                <p class="text-xs text-gray-500 mb-2">Manage your password</p>
                <a href="{{ route('dashboard.profile.password') }}" class="btn btn-secondary text-xs py-1 px-2.5">Change password</a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
