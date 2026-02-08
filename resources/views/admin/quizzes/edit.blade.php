@extends('layouts.dashboard')

@section('title', 'Edit Quiz')
@section('dashboard_heading', 'Edit Quiz')

@section('dashboard_content')
<div class="w-full space-y-6">
    {{-- Breadcrumb navigation --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('dashboard.quizzes.index') }}" class="hover:text-gray-900">Quizzes</a>
        <span>/</span>
        <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="hover:text-gray-900">{{ \Illuminate\Support\Str::limit($quiz->title, 30) }}</a>
        <span>/</span>
        <span class="text-gray-900">Edit</span>
    </nav>

    <div class="bg-white rounded-lg border border-gray-200 p-6 md:p-8">
            @if(session('error'))
                <div class="alert alert-error mb-6" role="alert">
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            @endif

            @if(isset($aiApiAvailable) && !$aiApiAvailable)
                <div class="alert alert-warning mb-6" role="alert">
                    <strong>AI question generation is disabled:</strong> No Gemini or DeepSeek API key is set. Add a key in @if(isset($staffPrefix) && false)<a href="{{ route('dashboard.settings.index') }}" class="underline font-medium">Dashboard → Settings</a>@else Dashboard → Settings (ask Super Admin) @endif to generate questions from source material or topics. Until then, add or edit questions manually.
                </div>
            @endif

            <form action="{{ route('dashboard.quizzes.update', $quiz) }}" method="post" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title', $quiz->title) }}" 
                        class="input" placeholder="e.g., Midterm Exam - Mathematics">
                </div>
                <!-- Exam type (for PDF reports) -->
                <div>
                    <label for="exam_type" class="block text-sm font-medium text-gray-700 mb-2">Exam type</label>
                    <select id="exam_type" name="exam_type" class="input">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Quiz::examTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type', $quiz->exam_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports (e.g. Quiz, Midsem, End of Semester).</p>
                </div>

                <!-- Class group (read-only); Course (within class group) -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 mb-6">
                    <p class="text-sm font-medium text-gray-700 mb-1">Class group</p>
                    <p class="text-gray-900">{{ $quiz->classGroup?->name ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Class group cannot be changed. You can only change the course below (from this class group’s attached courses).</p>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course *</label>
                        <select id="course_id" name="course_id" required class="input">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ old('course_id', $quiz->course_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Only courses attached to this quiz’s class group are listed.</p>
                    </div>

                    <div>
                        <label for="number_of_questions" class="block text-sm font-medium text-gray-700 mb-2">Number of Questions *</label>
                        <input type="number" id="number_of_questions" name="number_of_questions" min="1" max="250" 
                            value="{{ old('number_of_questions', $quiz->number_of_questions) }}" class="input">
                    </div>
                </div>

                <!-- Duration and Topics Grid -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" 
                            value="{{ old('duration_minutes', $quiz->duration_minutes) }}" class="input">
                    </div>

                    <div class="md:col-span-1">
                        <label for="topics-input" class="block text-sm font-medium text-gray-700 mb-2">Topics (for AI generation)</label>
                        @php
                            $topicsStr = $quiz->topics;
                            if (is_string($topicsStr)) {
                                $dec = json_decode($topicsStr, true);
                                if (is_array($dec)) {
                                    $topicsStr = implode(', ', array_column($dec, 'name'));
                                }
                            }
                        @endphp
                        <input type="hidden" name="topics" id="topics-value" value="{{ old('topics', $topicsStr) }}">
                        <input type="text" id="topics-input" autocomplete="off" placeholder="Type a topic, then press comma (,) to add"
                            class="input mb-2" aria-describedby="topic-tags-hint">
                        <div id="topic-tags" class="flex flex-wrap gap-2 min-h-[2rem]" role="list" aria-label="Added topics"></div>
                        <p id="topic-tags-hint" class="text-xs text-gray-500 mt-1">Add topics one by one; each appears as a tag below. AI will use these precise topics to generate questions.</p>
                    </div>
                </div>

                <!-- Source for AI (optional): topics only | paste script | upload file -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Source for AI questions (optional)</h3>
                    <p class="text-sm text-gray-500 mb-4">Choose one: <strong>Topics only</strong> (uses the topics field above), <strong>Paste script</strong> (optional text), or <strong>Upload file</strong> (optional file). If you leave script or file empty, topics are used. Leave all empty to skip AI generation.</p>
                    <div class="flex flex-wrap gap-4 mb-4" role="tablist">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="topics" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode', 'topics') === 'topics' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Topics only</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="paste" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode') === 'paste' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Paste script</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="source_mode" value="file" class="w-4 h-4 text-primary-600 border-gray-300" {{ old('source_mode') === 'file' ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Upload file</span>
                        </label>
                    </div>
                    <div id="source-paste-wrap" class="hidden mb-4">
                        <label for="source_script" class="block text-sm font-medium text-gray-700 mb-1">Paste your content (optional)</label>
                        <p class="text-xs text-gray-500 mb-2">Paste lecture notes or any text. Leave empty to use topics only.</p>
                        <textarea id="source_script" name="source_script" rows="6" class="input font-mono text-sm min-h-[8rem] max-h-80 overflow-y-auto resize-y w-full break-words whitespace-pre-wrap" placeholder="Paste your script or notes here...">{{ old('source_script') }}</textarea>
                    </div>
                    <div id="source-file-wrap" class="hidden">
                        <label for="source_file" class="block text-sm font-medium text-gray-700 mb-1">Upload file (optional)</label>
                        <p class="text-xs text-gray-500 mb-2">.txt, .pdf, or .docx. Leave empty to use topics only.</p>
                        <input type="file" id="source_file" name="source_file" accept=".txt,.pdf,.docx" 
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border file:border-primary-300 file:bg-primary-50 file:text-primary-700 file:font-medium hover:file:bg-primary-100">
                        <div id="source-file-progress-wrap" class="hidden mt-3">
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span id="source-file-progress-text">Uploading document...</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="source-file-progress-bar" class="h-full bg-primary-600 transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quiz Scheduling</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">Starts At (optional)</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" 
                                value="{{ old('starts_at', $quiz->starts_at?->format('Y-m-d\TH:i')) }}" class="input">
                        </div>

                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-2">Ends At (optional)</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" 
                                value="{{ old('ends_at', $quiz->ends_at?->format('Y-m-d\TH:i')) }}" class="input">
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="border-t border-gray-200 pt-6">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" 
                            {{ old('is_active', $quiz->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 group-hover:text-primary-600">Quiz is active</span>
                            <p class="text-xs text-gray-500">Students can access this quiz when active</p>
                        </div>
                    </label>
                </div>

                <!-- Result visibility (review / score after quiz) -->
                <div class="border-t border-gray-200 pt-6">
                    <label for="result_visibility" class="block text-sm font-medium text-gray-700 mb-2">Result visibility</label>
                    <select id="result_visibility" name="result_visibility" class="input">
                        @foreach(\App\Models\Quiz::resultVisibilityOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('result_visibility', $quiz->result_visibility ?? 'full_review_after_end') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Score only, full review after quiz end, or disabled (no score/review).</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-6">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Quiz
                    </button>
                    <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
    </div>
</div>
@push('scripts')
<script>
(function() {
    var topicsValue = document.getElementById('topics-value');
    var topicsInput = document.getElementById('topics-input');
    var tagsContainer = document.getElementById('topic-tags');
    if (!topicsValue || !topicsInput || !tagsContainer) return;

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function getTags() {
        var val = topicsValue.value || '';
        return parseTopics(val);
    }

    function setTags(tags) {
        topicsValue.value = tags.join(', ');
        renderTags();
    }

    function addTag(label) {
        var t = (label || '').trim();
        if (!t) return;
        var tags = getTags();
        if (tags.indexOf(t) !== -1) return;
        tags.push(t);
        setTags(tags);
    }

    function removeTag(index) {
        var tags = getTags();
        tags.splice(index, 1);
        setTags(tags);
    }

    function renderTags() {
        var tags = getTags();
        tagsContainer.innerHTML = '';
        tags.forEach(function(t, i) {
            var span = document.createElement('span');
            span.className = 'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 border border-primary-200';
            span.setAttribute('role', 'listitem');
            var text = document.createTextNode(t);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ml-1 rounded-full p-0.5 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-500';
            btn.setAttribute('aria-label', 'Remove topic ' + t);
            btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
            btn.addEventListener('click', function() { removeTag(i); });
            span.appendChild(text);
            span.appendChild(btn);
            tagsContainer.appendChild(span);
        });
    }

    topicsInput.addEventListener('keydown', function(e) {
        if (e.key === ',') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
        }
    });

    topicsInput.addEventListener('blur', function() {
        var v = topicsInput.value.trim();
        if (v) {
            addTag(v);
            topicsInput.value = '';
        }
    });

    renderTags();
})();

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('input[name="source_mode"]');
    if (!form) return;
    form = form.closest('form');
    if (!form) return;
    var pasteWrap = document.getElementById('source-paste-wrap');
    var fileWrap = document.getElementById('source-file-wrap');
    var fileInput = document.getElementById('source_file');
    var scriptEl = document.getElementById('source_script');
    var progressWrap = document.getElementById('source-file-progress-wrap');
    var progressBar = document.getElementById('source-file-progress-bar');

    function syncSourceMode() {
        var mode = form.querySelector('input[name="source_mode"]:checked');
        if (!mode) return;
        if (mode.value === 'paste') {
            if (pasteWrap) pasteWrap.classList.remove('hidden');
            if (fileWrap) fileWrap.classList.add('hidden');
            if (fileInput) fileInput.removeAttribute('name');
            if (scriptEl) scriptEl.setAttribute('name', 'source_script');
        } else if (mode.value === 'file') {
            if (pasteWrap) pasteWrap.classList.add('hidden');
            if (fileWrap) fileWrap.classList.remove('hidden');
            if (scriptEl) scriptEl.removeAttribute('name');
            if (fileInput) fileInput.setAttribute('name', 'source_file');
        } else {
            if (pasteWrap) pasteWrap.classList.add('hidden');
            if (fileWrap) fileWrap.classList.add('hidden');
            if (scriptEl) scriptEl.removeAttribute('name');
            if (fileInput) fileInput.removeAttribute('name');
        }
    }
    form.querySelectorAll('input[name="source_mode"]').forEach(function(r) {
        r.addEventListener('change', syncSourceMode);
    });
    syncSourceMode();

    form.addEventListener('submit', function() {
        var mode = form.querySelector('input[name="source_mode"]:checked');
        if (mode && mode.value === 'file' && fileInput && fileInput.files && fileInput.files.length) {
            if (progressWrap) progressWrap.classList.remove('hidden');
            if (progressBar) progressBar.style.width = '30%';
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
        }
    });
});
</script>
@endpush
@endsection
