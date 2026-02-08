@extends('layouts.dashboard')

@section('title', 'Session – ' . $session->student_index)
@section('dashboard_heading', 'Session – ' . $session->student_index)

@section('dashboard_content')
<div class="w-full space-y-3">
    <nav class="flex flex-wrap items-center gap-x-2 text-sm text-gray-500">
        <a href="{{ route('dashboard.quizzes.show', ['quiz' => $quiz, 'tab' => 'sessions']) }}" class="hover:text-primary-600 inline-flex items-center gap-1">← Back to scores</a>
        <span>·</span>
        <span class="font-medium text-gray-900 truncate max-w-[10rem] sm:max-w-none">{{ $quiz->title }}</span>
        <span>·</span>
        <span>Index {{ $session->student_index }}</span>
    </nav>

    {{-- Summary --}}
    <section class="bg-white rounded-lg border border-gray-200 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <h2 class="text-sm font-semibold text-gray-900">Summary</h2>
            <form method="post" action="{{ route('dashboard.quizzes.sessions.reset-ip', [$quiz, $session]) }}" onsubmit="return confirm('Reset IP lock?');">
                @csrf
                <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Reset IP Lock</button>
            </form>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
            <div><span class="text-gray-500 block">Index</span><span class="font-medium text-gray-900">{{ $session->student_index }}</span></div>
            <div><span class="text-gray-500 block">IP</span><span class="font-mono text-gray-900 truncate block" title="{{ $session->ip_address }}">{{ $session->ip_address }}</span></div>
            <div><span class="text-gray-500 block">Started</span><span class="text-gray-900">{{ $session->start_time?->format('M d, H:i') ?? '-' }}</span></div>
            <div><span class="text-gray-500 block">Ended</span><span class="text-gray-900">{{ $session->ended_at?->format('M d, H:i') ?? '-' }}</span></div>
            <div><span class="text-gray-500 block">Score</span>
                @if($session->result)
                    <span class="inline-block px-1.5 py-0.5 text-xs font-semibold rounded
                        @if($session->result->score >= 70) bg-green-100 text-green-800
                        @elseif($session->result->score >= 50) bg-amber-100 text-amber-800
                        @else bg-red-100 text-red-800
                        @endif">{{ $session->result->score }}%</span>
                @else<span class="text-gray-400">-</span>@endif
            </div>
            <div><span class="text-gray-500 block">Violations</span>
                @if($session->result)
                    <span class="inline-block px-1.5 py-0.5 text-xs font-semibold rounded {{ $session->result->violations_count > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ $session->result->violations_count }}</span>
                @else<span class="text-gray-400">-</span>@endif
            </div>
        </div>
    </section>

    {{-- Face Capture and Violation Log: at least two cards in one row, side by side --}}
    <div class="grid grid-cols-2 gap-3 min-w-0">
        {{-- Face Capture: profile-style small images --}}
        <section class="min-w-0 bg-white rounded-lg border border-gray-200 p-3" id="face-capture">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Face Capture</h2>
            <div class="flex flex-wrap gap-4">
                <div class="flex flex-col items-center">
                    <span class="text-xs text-gray-500 mb-1">1. At start</span>
                    @if($session->pre_face_image)
                        @php $preUrl = Str::startsWith($session->pre_face_image, 'http') ? $session->pre_face_image : asset('storage/' . $session->pre_face_image); @endphp
                        <button type="button" class="session-img-thumb rounded-lg border border-gray-200 overflow-hidden bg-gray-50 hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1" data-session-full-img="{{ $preUrl }}" data-session-img-alt="Face at start" aria-label="View full size">
                            <img src="{{ $preUrl }}" alt="Face at start" class="w-20 h-20 object-cover object-top" loading="lazy">
                        </button>
                        <span class="text-xs text-gray-500 mt-1">Click to enlarge</span>
                    @else
                        <div class="w-20 h-20 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400 text-xs">No image</div>
                    @endif
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-xs text-gray-500 mb-1">2. At end</span>
                    @if($session->post_face_image)
                        @php $postUrl = Str::startsWith($session->post_face_image, 'http') ? $session->post_face_image : asset('storage/' . $session->post_face_image); @endphp
                        <button type="button" class="session-img-thumb rounded-lg border border-gray-200 overflow-hidden bg-gray-50 hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1" data-session-full-img="{{ $postUrl }}" data-session-img-alt="Face at end" aria-label="View full size">
                            <img src="{{ $postUrl }}" alt="Face at end" class="w-20 h-20 object-cover object-top" loading="lazy">
                        </button>
                        @if($session->post_face_captured_at)
                            <span class="text-xs text-gray-500 mt-1">{{ $session->post_face_captured_at->format('M d, H:i') }}</span>
                        @else
                            <span class="text-xs text-gray-500 mt-1">Click to enlarge</span>
                        @endif
                    @else
                        <div class="w-20 h-20 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400 text-xs">No image</div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Violation Log: each violation in a row (#, Type, Details inline) --}}
        <section class="min-w-0 bg-white rounded-lg border border-gray-200 p-3">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Violation Log</h2>
            @if($session->violations->isEmpty())
                <div class="text-center py-4 text-gray-500 text-xs">No violations recorded.</div>
            @else
                <ul class="space-y-1.5 text-xs">
                    @foreach($session->violations as $idx => $v)
                        <li class="flex flex-wrap items-center gap-2 py-1.5 border-b border-gray-100 last:border-0 hover:bg-gray-50 rounded px-1 -mx-1">
                            <span class="text-gray-600 font-medium tabular-nums">{{ $idx + 1 }}.</span>
                            <span class="px-1.5 py-0.5 rounded font-medium bg-red-100 text-red-800">{{ ucfirst(str_replace('_', ' ', $v->type)) }}</span>
                            @if($v->metadata)
                                <span class="text-gray-600"><pre class="inline text-xs bg-gray-50 p-1 rounded overflow-x-auto max-w-[140px] sm:max-w-xs whitespace-pre-wrap break-words">{{ is_string($v->metadata) ? $v->metadata : json_encode($v->metadata, JSON_PRETTY_PRINT) }}</pre></span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div>
        <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">← Back to Quiz</a>
    </div>
</div>

{{-- Lightbox --}}
<div id="session-img-lightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="View image">
    <button type="button" id="session-img-lightbox-close" class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 focus:outline-none" aria-label="Close">×</button>
    <img id="session-img-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] w-auto h-auto object-contain rounded">
</div>

<script>
(function() {
    var lightbox = document.getElementById('session-img-lightbox');
    var lightboxImg = document.getElementById('session-img-lightbox-img');
    var closeBtn = document.getElementById('session-img-lightbox-close');
    if (!lightbox || !lightboxImg) return;
    function open(src, alt) { lightboxImg.src = src; lightboxImg.alt = alt || ''; lightbox.classList.remove('hidden'); lightbox.classList.add('flex'); document.body.style.overflow = 'hidden'; }
    function close() { lightbox.classList.add('hidden'); lightbox.classList.remove('flex'); document.body.style.overflow = ''; }
    document.querySelectorAll('.session-img-thumb').forEach(function(btn) {
        btn.addEventListener('click', function() { var s = btn.getAttribute('data-session-full-img'); if (s) open(s, btn.getAttribute('data-session-img-alt')); });
    });
    if (closeBtn) closeBtn.addEventListener('click', close);
    lightbox.addEventListener('click', function(e) { if (e.target === lightbox) close(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') close(); });
})();
</script>
@endsection
