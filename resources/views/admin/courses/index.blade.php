@extends('layouts.dashboard')

@section('title', 'Courses')
@section('dashboard_heading', 'Courses')

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Courses</span>
        </div>
        <a href="{{ route('dashboard.courses.create') }}" class="btn btn-primary text-sm">Add course</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    {{-- Unassigned courses (no examiners) --}}
    @if($unassignedCourses->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <details class="group/details" open>
                <summary class="flex items-center justify-between cursor-pointer list-none px-4 py-3 bg-gray-50 hover:bg-gray-100 text-sm font-semibold text-gray-700">
                    <span>Unassigned</span>
                    <span class="text-gray-500 font-normal">{{ $unassignedCourses->count() }} course(s)</span>
                </summary>
                <div class="border-t border-gray-200 overflow-x-auto">
                    @include('admin.courses.partials.courses-table', ['courses' => $unassignedCourses, 'isSuperAdmin' => $isSuperAdmin])
                </div>
            </details>
        </section>
    @endif

    {{-- Lecturers with courses --}}
    @forelse($lecturers as $lecturer)
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <details class="group/details">
                <summary class="flex items-center justify-between cursor-pointer list-none px-4 py-3 bg-slate-50 hover:bg-slate-100 text-sm font-semibold text-gray-800">
                    <span>{{ $lecturer->name ?? $lecturer->username }}</span>
                    <span class="text-gray-500 font-normal">{{ $lecturer->courses_count ?? $lecturer->courses->count() }} course(s)</span>
                </summary>
                <div class="border-t border-gray-200 overflow-x-auto">
                    @include('admin.courses.partials.courses-table', ['courses' => $lecturer->courses, 'isSuperAdmin' => $isSuperAdmin])
                </div>
            </details>
        </section>
    @empty
        @if($unassignedCourses->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                <p class="text-sm text-gray-500">No courses yet. Create one to assign examiners and create quizzes.</p>
                <a href="{{ route('dashboard.courses.create') }}" class="mt-3 inline-flex text-sm text-primary-600 font-medium hover:underline">Add course</a>
            </div>
        @endif
    @endforelse

    @if($lecturers->hasPages())
        <div class="mt-4">{{ $lecturers->links() }}</div>
    @endif
</div>
@endsection
