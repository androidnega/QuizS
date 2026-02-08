{{-- Shown only on actual mobile phones on student/quiz pages; examiners and admin can use the site on mobile --}}
@php
$userAgent = request()->header('User-Agent') ?? '';
$isMobilePhone = preg_match('/(iPhone|iPod|Android.*Mobile|BlackBerry|IEMobile|Opera Mini)/i', $userAgent) && 
                 !preg_match('/(iPad|Android(?!.*Mobile)|Tablet)/i', $userAgent);
$isStaffArea = request()->routeIs('examiner.*') || request()->routeIs('admin.*');
@endphp

@if($isMobilePhone && !$isStaffArea)
<div id="quizsnap-mobile-notice" class="flex items-start gap-3 p-4 mx-4 mt-4 rounded-lg border-2 border-red-500 bg-red-50 text-red-900" role="alert" aria-live="polite">
    <span class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white text-xl font-bold" aria-hidden="true">!</span>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-red-800">You're on a phone</p>
        <p class="text-sm text-red-800 mt-0.5">For the best experience—taking quizzes and viewing results—use a computer or tablet. Student quiz and result panels are not fully supported on phones.</p>
    </div>
</div>
@endif
