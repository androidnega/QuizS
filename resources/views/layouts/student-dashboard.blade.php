@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-screen flex flex-col">
    <header class="shrink-0 bg-white border-b border-gray-200">
        <div class="mx-auto flex h-12 max-w-4xl items-center justify-between gap-3 px-4">
            <a href="{{ route('dashboard') }}" class="font-bold text-gray-900 no-underline flex items-center gap-1.5 shrink-0" title="Dashboard">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold">Q</span>
                <span class="hidden sm:inline text-sm">QuizSnap</span>
            </a>

            <nav class="flex items-center gap-1 flex-1 justify-center min-w-0" aria-label="Dashboard navigation">
                <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded-md text-xs font-medium {{ request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' }}">Dashboard</a>
                <a href="{{ route('dashboard.my-quizzes') }}" class="px-2.5 py-1.5 rounded-md text-xs font-medium {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' }}">My quizzes</a>
            </nav>

            @if(isset($student) && $student)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="flex items-center gap-2 rounded-lg py-2 pl-2 pr-2 sm:pr-3 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-300" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown">
                    <span class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-semibold text-sm">{{ $student->initials }}</span>
                    <span class="hidden sm:block text-left max-w-[120px] truncate">
                        <span class="block text-sm font-medium text-gray-900 truncate">{{ $student->first_name }}</span>
                        <span class="block text-xs text-gray-500 font-mono truncate">{{ $student->index_number }}</span>
                    </span>
                    <svg class="w-4 h-4 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="student-profile-dropdown" class="absolute right-0 mt-1 w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-lg z-50 hidden" role="menu">
                    <div class="px-3 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $student->display_name }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $student->index_number }}</p>
                    </div>
                    <a href="{{ route('dashboard.my-profile') }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Profile</a>
                    <form action="{{ route('student.account.logout') }}" method="post" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Log out</button>
                    </form>
                </div>
            </div>
            <script>
            (function(){var btn=document.getElementById('student-profile-btn');var drop=document.getElementById('student-profile-dropdown');if(!btn||!drop)return;function open(){drop.classList.remove('hidden');btn.setAttribute('aria-expanded','true');}function close(){drop.classList.add('hidden');btn.setAttribute('aria-expanded','false');}btn.addEventListener('click',function(e){e.stopPropagation();if(drop.classList.contains('hidden'))open();else close();});document.addEventListener('click',function(){close();});drop.addEventListener('click',function(e){e.stopPropagation();});})();
            </script>
            @else
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Profile</a>
                <form action="{{ route('student.account.logout') }}" method="post" class="inline">@csrf<button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Log out</button></form>
            </div>
            @endif
        </div>
    </header>
    <main class="flex-1 mx-auto w-full max-w-4xl px-4 py-4">
        @yield('dashboard_content')
    </main>
</div>
@endsection
