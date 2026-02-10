@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-screen flex flex-col">
    <header class="shrink-0 bg-white border-b border-gray-200">
        <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-4">
            <a href="{{ route('dashboard') }}" class="font-bold text-gray-900 no-underline flex items-center gap-2" title="Dashboard">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600 text-white text-sm">Q</span>
                <span>QuizSnap</span>
            </a>
            <nav class="flex items-center gap-1 sm:gap-2" aria-label="Dashboard navigation">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}" title="Dashboard">Dashboard</a>
                <a href="{{ route('dashboard.my-quizzes') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">My quizzes</a>
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard.my-profile*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">Profile</a>
                <form action="{{ route('student.account.logout') }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">Log out</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="flex-1 mx-auto w-full max-w-4xl px-4 py-6">
        @yield('dashboard_content')
    </main>
</div>
@endsection
