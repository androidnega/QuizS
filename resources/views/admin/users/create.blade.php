@extends('layouts.dashboard')

@section('title', 'Add user')
@section('dashboard_heading', 'Add user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 mb-4 sm:mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600 shrink-0">Dashboard</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600 shrink-0">User management</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Add user</span>
        </div>

        <div class="card p-4 sm:p-6 w-full min-w-0 max-w-full overflow-hidden">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Add user</h1>

            <form action="{{ route('dashboard.users.store') }}" method="post" class="space-y-4 w-full min-w-0">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required class="input w-full max-w-full min-w-0 @error('username') border-danger-500 @enderror">
                    @error('username')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional, for password reset)</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="input w-full max-w-full min-w-0 @error('email') border-danger-500 @enderror" placeholder="user@example.com">
                    @error('email')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="input w-full max-w-full min-w-0 @error('name') border-danger-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" required class="input w-full max-w-full min-w-0 @error('role') border-danger-500 @enderror">
                        <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner</option>
                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    @error('role')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sms_allocation" class="block text-sm font-medium text-gray-700 mb-1">SMS allocation (for Examiner)</label>
                    <input type="number" name="sms_allocation" id="sms_allocation" value="{{ old('sms_allocation', 0) }}" min="0" step="1" class="input w-full max-w-full min-w-0 @error('sms_allocation') border-danger-500 @enderror" placeholder="0">
                    <p class="mt-1 text-xs text-gray-500">Number of SMS the examiner can use to send login tokens to students (e.g. 20).</p>
                    @error('sms_allocation')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div id="institution-field">
                    <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">Institution (for Examiner)</label>
                    <select name="institution_id" id="institution_id" class="input w-full max-w-full min-w-0 @error('institution_id') border-danger-500 @enderror">
                        <option value="">— Select institution —</option>
                        @foreach($institutions ?? [] as $inst)
                            <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                        @endforeach
                    </select>
                    @error('institution_id')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div id="course-field" class="min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assigned courses (for Examiner)</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 min-w-0">
                        @foreach($courses as $c)
                            <label class="flex items-start sm:items-center gap-2 min-w-0">
                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', [])) ? 'checked' : '' }} class="mt-0.5 shrink-0">
                                <span class="text-sm break-words">{{ $c->code ?? $c->name }} — {{ $c->name }}</span>
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
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="password" name="password" id="password" required class="input flex-1 min-w-0 max-w-full @error('password') border-danger-500 @enderror" minlength="8" autocomplete="new-password">
                        <button type="button" id="generate-password" class="btn btn-secondary shrink-0 text-sm">Generate</button>
                        <button type="button" id="copy-password" class="p-2 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-400 shrink-0" title="Copy password">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">At least 8 characters, including one letter and one number.</p>
                    @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="input w-full max-w-full min-w-0">
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn btn-primary shrink-0">Create user</button>
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary shrink-0">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    const digits = '23456789';
    const chars = letters + digits + '!@#$%&*';

    function generatePassword() {
        let p = '';
        p += letters[Math.floor(Math.random() * letters.length)];
        p += digits[Math.floor(Math.random() * digits.length)];
        for (let i = 0; i < 8; i++) {
            p += chars[Math.floor(Math.random() * chars.length)];
        }
        return p.split('').sort(() => Math.random() - 0.5).join('');
    }

    document.getElementById('generate-password').addEventListener('click', function() {
        const pw = generatePassword();
        document.getElementById('password').value = pw;
        document.getElementById('password_confirmation').value = pw;
    });

    document.getElementById('copy-password').addEventListener('click', function() {
        const pw = document.getElementById('password').value;
        if (!pw) return;
        navigator.clipboard.writeText(pw).then(function() {
            const btn = document.getElementById('copy-password');
            const orig = btn.innerHTML;
            btn.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            btn.title = 'Copied!';
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.title = 'Copy password';
            }, 1500);
        });
    });
})();
</script>
@endpush
@endsection
