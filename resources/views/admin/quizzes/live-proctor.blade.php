@extends('layouts.dashboard')

@section('title', 'Live proctor – ' . $quiz->title)
@section('dashboard_heading', 'Live proctor')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4">
    <nav class="flex flex-wrap items-center gap-x-2 text-sm text-gray-500">
        <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}" class="hover:text-primary-600 inline-flex items-center gap-1">← Back to quiz</a>
        <span>·</span>
        <span class="font-medium text-gray-900 truncate max-w-[14rem] sm:max-w-none">{{ $quiz->title }}</span>
    </nav>

    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-600 mb-4">Students currently taking this quiz appear below. Camera feed updates every few seconds. Only sessions with recent activity (heartbeat or proctor feed) in the last 60 seconds are shown.</p>
        <div id="live-proctor-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 min-w-0">
            {{-- Populated by JS --}}
        </div>
        <div id="live-proctor-empty" class="hidden text-center py-12 text-gray-500">
            <p class="text-sm">No students are currently writing this quiz.</p>
            <p class="text-xs mt-1">This list refreshes every 5 seconds.</p>
        </div>
        <div id="live-proctor-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var quizId = {{ $quiz->id }};
    var liveSessionsUrl = "{{ route('dashboard.quizzes.live-sessions', $quiz) }}";
    var frameUrlTemplate = "{{ route('dashboard.quizzes.sessions.proctor-frame', [$quiz, '__ID__']) }}";
    var grid = document.getElementById('live-proctor-grid');
    var emptyEl = document.getElementById('live-proctor-empty');
    var loadingEl = document.getElementById('live-proctor-loading');

    function frameUrl(sessionId) {
        return frameUrlTemplate.replace('__ID__', String(sessionId)) + '?t=' + (Date.now() / 3000 | 0);
    }

    function renderSessions(sessions) {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (!sessions || sessions.length === 0) {
            if (emptyEl) emptyEl.classList.remove('hidden');
            if (grid) grid.innerHTML = '';
            return;
        }
        if (emptyEl) emptyEl.classList.add('hidden');
        if (!grid) return;
        var existing = {};
        grid.querySelectorAll('[data-session-id]').forEach(function(el) { existing[el.getAttribute('data-session-id')] = el; });
        var seen = {};
        sessions.forEach(function(s) {
            seen[s.id] = true;
            var card = existing[s.id];
            if (!card) {
                card = document.createElement('div');
                card.setAttribute('data-session-id', s.id);
                card.className = 'rounded-lg border border-gray-200 overflow-hidden bg-gray-50 min-w-0';
                card.innerHTML =
                    '<div class="px-3 py-2 border-b border-gray-200 bg-white"><span class="font-medium text-gray-900">' + (s.student_index || 'Index ' + s.id) + '</span></div>' +
                    '<div class="aspect-video bg-gray-900 flex items-center justify-center min-h-[120px]">' +
                    '<img src="" alt="Camera feed" class="w-full h-full object-contain proctor-frame-img" data-session-id="' + s.id + '" loading="lazy" referrerpolicy="no-referrer">' +
                    '</div>';
                grid.appendChild(card);
            }
            var img = card.querySelector('.proctor-frame-img');
            if (img) img.src = frameUrl(s.id);
        });
        Object.keys(existing).forEach(function(id) {
            if (!seen[id]) existing[id].remove();
        });
    }

    function fetchLiveSessions() {
        fetch(liveSessionsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                renderSessions(data && data.sessions ? data.sessions : []);
            })
            .catch(function() { renderSessions([]); });
    }

    fetchLiveSessions();
    setInterval(fetchLiveSessions, 5000);
    setInterval(function() {
        grid.querySelectorAll('.proctor-frame-img').forEach(function(img) {
            var id = img.getAttribute('data-session-id');
            if (id) img.src = frameUrl(id);
        });
    }, 3000);
})();
</script>
@endpush
@endsection
