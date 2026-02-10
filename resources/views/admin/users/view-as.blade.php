@extends('layouts.dashboard')

@section('title', 'View as Examiner')
@section('dashboard_heading', 'View as Examiner')

@section('dashboard_content')
<div class="w-full max-w-md mx-auto">
    <div class="card p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">View Examiner Dashboard</h2>
            <p class="text-sm text-gray-600">
                Enter your admin password to view the dashboard as <strong>{{ $user->username }}</strong>.
            </p>
        </div>

        <form action="{{ route('dashboard.users.view-as', $user) }}" method="post">
            @csrf
            
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Your Admin Password
                </label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    required 
                    autofocus
                    class="input w-full @error('password') border-danger-500 @enderror"
                    placeholder="Enter your password"
                >
                @error('password')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
                @if(session('error'))
                    <p class="mt-1 text-sm text-danger-600">{{ session('error') }}</p>
                @endif
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary flex-1">
                    View Dashboard
                </button>
                <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
