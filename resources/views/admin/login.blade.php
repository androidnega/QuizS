@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="card p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Log in</h1>
                <p class="text-sm text-gray-600 mt-1">Enter your credentials</p>
            </div>

            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-danger-50 border border-danger-200 text-danger-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mb-4 p-3 rounded-lg bg-primary-50 border border-primary-200 text-primary-800 text-sm">
                    {{ session('info') }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                        class="input w-full @error('username') border-danger-500 @enderror">
                    @error('username')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                        class="input w-full @error('password') border-danger-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full">Log in</button>
            </form>
        </div>
    </div>
</div>
@endsection
