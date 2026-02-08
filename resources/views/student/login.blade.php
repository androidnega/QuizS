@extends('layouts.student')

@section('title', 'Enter your index number')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Enter your index number</h1>
            @if(isset($quiz) && $quiz)
                <p class="text-gray-600 text-sm mb-2">{{ $quiz->title }}</p>
            @endif
            <p class="text-gray-600 text-sm mb-6">No password. Click Continue to verify.</p>

            <form id="login-form" class="space-y-4">
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" id="index_number" name="index_number" required placeholder="BC/ITS/24/047" class="input">
                </div>

                <div id="login-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="login-error-text"></div>
                </div>

                <button type="submit" class="btn btn-action w-full py-2.5 text-sm font-semibold">
                    <span id="btn-text">Continue</span>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const errEl = document.getElementById('login-error');
    const errText = document.getElementById('login-error-text');
    const btnText = document.getElementById('btn-text');
    errEl.classList.add('hidden');
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btnText.textContent = 'Verifying...';
    fetch('{{ route("student.verify.index") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            index_number: document.getElementById('index_number').value.trim()
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            errText.textContent = data.message || 'Verification failed.';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btnText.textContent = 'Continue';
        }
    })
    .catch(() => {
        errText.textContent = 'Network error. Please try again.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btnText.textContent = 'Continue';
    });
});
</script>
@endpush
@endsection
