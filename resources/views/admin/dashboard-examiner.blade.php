@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-8">
    @php
        $examiner = auth()->user();
        $smsRemaining = $examiner && $examiner->isExaminer() ? $examiner->sms_remaining : 0;
        $showLowSmsWarning = $examiner && $examiner->isExaminer() && $smsRemaining < 100 && $smsRemaining > 0;
    @endphp
    
    {{-- Faculty/Department Notice --}}
    @if(isset($needsFacultyDepartment) && $needsFacultyDepartment)
    <div id="faculty-department-notice" class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 mb-6" role="alert">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-yellow-900">Complete Your Profile</p>
                <p class="mt-1 text-sm text-yellow-800">Please select your faculty and department to continue. This information is required for your account.</p>
                <form id="faculty-department-form" class="mt-4 space-y-3" onsubmit="updateFacultyDepartment(event)">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="faculty_select" class="block text-xs font-medium text-yellow-900 mb-1">Faculty</label>
                            <select name="faculty_id" id="faculty_select" required class="input w-full text-sm @error('faculty_id') border-red-500 @enderror" onchange="loadDepartments(this.value)">
                                <option value="">— Select Faculty —</option>
                                @if($examiner && $examiner->institution_id)
                                    @foreach(\App\Models\Faculty::where('institution_id', $examiner->institution_id)->orderBy('name')->get() as $faculty)
                                        <option value="{{ $faculty->id }}" {{ old('faculty_id', $examiner->faculty_id) == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="department_select" class="block text-xs font-medium text-yellow-900 mb-1">Department</label>
                            <select name="department_id" id="department_select" required class="input w-full text-sm @error('department_id') border-red-500 @enderror" disabled>
                                <option value="">— Select Department —</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-primary text-sm px-4 py-2">Save</button>
                        <span id="faculty-dept-error" class="text-xs text-red-600 hidden"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Low SMS Warning Banner --}}
    @if($showLowSmsWarning)
    <div id="low-sms-warning" class="rounded-lg border border-amber-200 bg-amber-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-amber-900">Low SMS Balance</p>
            <p class="mt-1 text-sm text-amber-800">You have <strong>{{ $smsRemaining }}</strong> SMS remaining. Please contact your administrator to reload your SMS allocation so you can continue sending login tokens to students.</p>
        </div>
        <button type="button" onclick="dismissLowSmsWarning()" class="flex-shrink-0 text-amber-600 hover:text-amber-800 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif
    
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Overview</h2>
        <p class="mt-1 text-sm text-gray-500">Manage class groups, quizzes, and view session results.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('dashboard.quizzes.index') }}" class="flex flex-col rounded-lg border border-primary-200 bg-primary-100 p-5 hover:bg-primary-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-primary-800">Quizzes</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-primary-900 sm:text-3xl">{{ $stats['quizzes'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-primary-200 text-primary-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-primary-700">View quizzes</p>
        </a>
        <a href="{{ route('dashboard.class-groups.index') }}" class="flex flex-col rounded-lg border border-action-200 bg-action-100 p-5 hover:bg-action-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-action-800">Class Groups</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-action-900 sm:text-3xl">{{ $classGroups->count() }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-action-200 text-action-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-action-700">Manage students per group</p>
        </a>
        <div class="flex flex-col rounded-lg border border-primary-200 bg-primary-100 p-5 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-primary-800">Sessions</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-primary-900 sm:text-3xl">{{ $stats['sessions'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-primary-200 text-primary-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-primary-700">View from quiz pages</p>
        </div>
        <div class="flex flex-col rounded-lg border border-action-200 bg-action-100 p-5 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-action-800">Results</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-action-900 sm:text-3xl">{{ $stats['results'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-action-200 text-action-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-action-700">View from quiz pages</p>
        </div>
    </div>

    {{-- Quick actions (compact) --}}
    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick actions</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-primary-600 rounded border border-primary-600 hover:bg-primary-700">Create quiz</a>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-800 bg-action-100 rounded border border-action-200 hover:bg-action-200">New class group</a>
            @endcan
            <a href="{{ route('dashboard.quizzes.index') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100">All quizzes</a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function() {
    // Low SMS Warning Dismissal (12-18 hours)
    const WARNING_KEY = 'low_sms_warning_dismissed';
    const DISMISS_HOURS = 15; // 15 hours (between 12-18)
    
    function dismissLowSmsWarning() {
        const warning = document.getElementById('low-sms-warning');
        if (warning) {
            warning.style.display = 'none';
            const dismissUntil = Date.now() + (DISMISS_HOURS * 60 * 60 * 1000);
            localStorage.setItem(WARNING_KEY, dismissUntil.toString());
        }
    }
    
    function shouldShowWarning() {
        const dismissed = localStorage.getItem(WARNING_KEY);
        if (!dismissed) return true;
        const dismissUntil = parseInt(dismissed, 10);
        return Date.now() > dismissUntil;
    }
    
    // Hide warning if dismissed and still valid
    const warning = document.getElementById('low-sms-warning');
    if (warning && !shouldShowWarning()) {
        warning.style.display = 'none';
    }
    
    // Make dismiss function global
    window.dismissLowSmsWarning = dismissLowSmsWarning;
})();

// Faculty/Department form handling
function loadDepartments(facultyId) {
    const deptSelect = document.getElementById('department_select');
    deptSelect.innerHTML = '<option value="">Loading...</option>';
    deptSelect.disabled = true;
    
    if (!facultyId) {
        deptSelect.innerHTML = '<option value="">— Select Department —</option>';
        return;
    }
    
    fetch(`{{ route('dashboard.departments.by-faculty', ['faculty' => '__FACULTY_ID__']) }}`.replace('__FACULTY_ID__', facultyId), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => response.json())
    .then(data => {
        deptSelect.innerHTML = '<option value="">— Select Department —</option>';
        data.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.id;
            option.textContent = dept.name;
            deptSelect.appendChild(option);
        });
        deptSelect.disabled = false;
    })
    .catch(error => {
        console.error('Error loading departments:', error);
        deptSelect.innerHTML = '<option value="">Error loading departments</option>';
    });
}

function updateFacultyDepartment(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const errorEl = document.getElementById('faculty-dept-error');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    fetch('{{ route("dashboard.examiner.update-faculty-department") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide notice and reload page
            document.getElementById('faculty-department-notice').style.display = 'none';
            window.location.reload();
        } else {
            errorEl.textContent = data.message || 'Failed to update. Please try again.';
            errorEl.classList.remove('hidden');
        }
    })
    .catch(error => {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save';
    });
}

// Load departments if faculty is pre-selected
@if($examiner && $examiner->faculty_id)
    document.addEventListener('DOMContentLoaded', function() {
        loadDepartments({{ $examiner->faculty_id }});
        @if($examiner->department_id)
            setTimeout(() => {
                document.getElementById('department_select').value = {{ $examiner->department_id }};
            }, 500);
        @endif
    });
@endif
</script>
@endpush
@endsection
