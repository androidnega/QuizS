@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div>
        <p class="text-gray-600">Courses, users, class groups (view only), and system settings</p>
    </div>

    {{-- Update mode: when on, only staff can log in; others see maintenance page --}}
    <section class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-amber-900">Update mode</h2>
                <p class="text-xs text-amber-800 mt-0.5">When on, only Super Admins and Examiners can sign in at <code class="bg-amber-100 px-1 rounded">/login</code>. Everyone else sees a maintenance page.</p>
            </div>
            <form method="post" action="{{ route('dashboard.settings.update-mode') }}" class="flex items-center gap-3">
                @csrf
                <span class="text-sm font-medium {{ $update_mode ?? false ? 'text-amber-700' : 'text-gray-600' }}">{{ ($update_mode ?? false) ? 'On' : 'Off' }}</span>
                <button type="submit" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 {{ ($update_mode ?? false) ? 'bg-amber-500' : 'bg-gray-200' }}" role="switch" aria-checked="{{ ($update_mode ?? false) ? 'true' : 'false' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ ($update_mode ?? false) ? 'translate-x-5' : 'translate-x-1' }}" style="margin-top: 2px;"></span>
                </button>
            </form>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Staff users</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ $overview['users'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Courses</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ $overview['courses'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Class groups</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ $overview['class_groups'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Quiz sessions</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ $overview['sessions'] ?? 0 }}</p>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick links</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <a href="{{ route('dashboard.class-groups.index') }}" class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-gray-100 hover:border-gray-300 transition-colors" title="Manage student groups and assign courses">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">Class Groups</span>
                    <p class="mt-0.5 text-xs text-gray-500">Manage student groups and assign courses to them.</p>
                </div>
            </a>
            <a href="{{ route('dashboard.courses.index') }}" class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-gray-100 hover:border-gray-300 transition-colors" title="Create and manage course catalog">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">Courses</span>
                    <p class="mt-0.5 text-xs text-gray-500">Create and manage the course catalog for quizzes.</p>
                </div>
            </a>
            <a href="{{ route('dashboard.settings.index') }}" class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-gray-100 hover:border-gray-300 transition-colors" title="Configure app, mail, AI, and Cloudinary">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">Settings</span>
                    <p class="mt-0.5 text-xs text-gray-500">Configure app name, mail, AI keys, and Cloudinary.</p>
                </div>
            </a>
            <a href="{{ route('dashboard.users.index') }}" class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 hover:bg-gray-100 hover:border-gray-300 transition-colors" title="Manage staff (Super Admin and Examiners)">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">Users</span>
                    <p class="mt-0.5 text-xs text-gray-500">Manage staff accounts (Super Admin and examiners).</p>
                </div>
            </a>
            <a href="{{ route('dashboard.system.reset.index') }}" class="flex gap-3 rounded-lg border border-danger-200 bg-danger-50 p-3 hover:bg-danger-100 hover:border-danger-300 transition-colors" title="Clear data or full system reset (use with caution)">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-white text-danger-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">Reset</span>
                    <p class="mt-0.5 text-xs text-gray-500">Clear quiz/course data or full system reset. Use with caution.</p>
                </div>
            </a>
        </div>
    </section>
</div>
@endsection
