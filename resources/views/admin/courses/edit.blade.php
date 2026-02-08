@extends('layouts.dashboard')

@section('title', 'Edit course')
@section('dashboard_heading', 'Edit course')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.courses.index') }}" class="hover:text-primary-600">Courses</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Edit {{ $course->name }}</span>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit course</h1>

            <form action="{{ route('dashboard.courses.update', $course) }}" method="post" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Course code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $course->code) }}" required maxlength="64" class="input w-full">
                    @error('code')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Course name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $course->name) }}" required maxlength="255" class="input w-full">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign examiners</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        @forelse($examiners as $e)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="examiner_ids[]" value="{{ $e->id }}" {{ in_array($e->id, old('examiner_ids', $course->examiners->pluck('id')->all())) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $e->username }}{{ $e->name ? ' (' . $e->name . ')' : '' }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No examiners yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('dashboard.courses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
