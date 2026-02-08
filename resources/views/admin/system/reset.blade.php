@extends('layouts.dashboard')

@section('title', 'Reset System')
@section('dashboard_heading', 'System Reset')

@section('dashboard_content')
<div class="w-full space-y-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Start over</h1>
        <p class="text-gray-600 mb-6">Choose how much to clear. You will need to enter your password and type <strong>RESET</strong> to confirm.</p>

        @if(session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error mb-6">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <form action="{{ route('dashboard.system.reset') }}" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                @csrf
                <div class="space-y-4 mb-6">
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Your password <span class="text-danger-600">*</span></label>
                        <input type="password" name="admin_password" id="admin_password" required class="input w-full" placeholder="Enter your password" autocomplete="current-password">
                        @error('admin_password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">What do you want to clear?</label>
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:border-primary-300 cursor-pointer">
                                <input type="radio" name="reset_type" value="data_only" {{ old('reset_type', 'data_only') === 'data_only' ? 'checked' : '' }} class="mt-1">
                                <span><strong>Clear system data only</strong> — Removes all quizzes, courses, student lists, results, and related data. <strong>All users stay</strong> (you and examiners). You can add courses and quizzes again right away.</span>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:border-primary-300 cursor-pointer">
                                <input type="radio" name="reset_type" value="all_except_super_admin" {{ old('reset_type') === 'all_except_super_admin' ? 'checked' : '' }} class="mt-1">
                                <span><strong>Clear everything except Super Admin</strong> — Same as above, plus <strong>removes all examiner accounts</strong>. Only Super Admin remains. Use when you want to remove all examiners and start fresh.</span>
                            </label>
                        </div>
                        @error('reset_type')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">Type the word <strong>RESET</strong> below to confirm <span class="text-danger-600">*</span></label>
                        <input type="text" name="confirm" id="confirm" value="{{ old('confirm') }}" required class="input w-full uppercase" placeholder="RESET" autocomplete="off">
                        @error('confirm')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit" class="btn bg-danger-600 hover:bg-danger-700 text-white">
                    Clear and start over
                </button>
            </form>
        </div>
</div>
@endsection
