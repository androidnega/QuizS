@extends('layouts.student-dashboard')

@section('title', 'Course Materials')
@php $dashboardTitle = 'Course Materials'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">← Dashboard</a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Course Materials</h1>
        <p class="text-gray-600 mt-1">Weekly course files and notes</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @for($week = 1; $week <= 3; $week++)
        <button type="button" class="week-btn rounded-lg border border-gray-200 bg-gray-50 p-4 text-center hover:bg-gray-100 transition-colors" data-week="{{ $week }}">
            <h3 class="text-base font-semibold text-gray-900">Week {{ $week }}</h3>
            <p class="text-xs text-gray-500 mt-1">Click to view</p>
        </button>
        @endfor
    </div>
</div>

{{-- Coming Soon Modal --}}
<div id="coming-soon-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50" aria-modal="true" aria-labelledby="coming-soon-title" role="dialog">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 id="coming-soon-title" class="text-lg font-semibold text-gray-900">Week <span id="coming-soon-week"></span></h2>
            <button type="button" id="coming-soon-close" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none text-xl" aria-label="Close">×</button>
        </div>
        <div class="px-5 py-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-base font-medium text-gray-900 mb-2">Coming Soon</p>
            <p class="text-sm text-gray-600">Course materials for this week will be available soon.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var comingSoonModal = document.getElementById('coming-soon-modal');
    var comingSoonClose = document.getElementById('coming-soon-close');
    var comingSoonWeek = document.getElementById('coming-soon-week');
    var weekButtons = document.querySelectorAll('.week-btn');
    
    if (comingSoonModal) {
        function openComingSoonModal(week) {
            if (comingSoonWeek) comingSoonWeek.textContent = week;
            comingSoonModal.classList.remove('hidden');
            comingSoonModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeComingSoonModal() {
            comingSoonModal.classList.add('hidden');
            comingSoonModal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        weekButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var week = this.getAttribute('data-week');
                openComingSoonModal(week);
            });
        });
        if (comingSoonClose) comingSoonClose.addEventListener('click', closeComingSoonModal);
        comingSoonModal.addEventListener('click', function(e) {
            if (e.target === comingSoonModal) closeComingSoonModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !comingSoonModal.classList.contains('hidden')) {
                closeComingSoonModal();
            }
        });
    }
})();
</script>
@endpush
@endsection
