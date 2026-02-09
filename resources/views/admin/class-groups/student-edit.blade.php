@extends('layouts.dashboard')

@section('title', 'Edit index — ' . $classGroup->name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-pen text-primary-600"></i> Edit student index</span>
@endsection

@section('dashboard_content')
<div class="w-full max-w-md">
    @if(session('error'))
        <div class="alert alert-error mb-4">{{ session('error') }}</div>
    @endif

    <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 mb-6">
        <i class="fas fa-arrow-left"></i> Back to student list
    </a>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form action="{{ route('dashboard.class-groups.students.update', [$classGroup, $student]) }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                <input type="text" name="index_number" id="index_number" required maxlength="64" class="input w-full" value="{{ old('index_number', $student->index_number) }}">
                @error('index_number')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                <input type="text" name="student_name" id="student_name" maxlength="255" class="input w-full" value="{{ old('student_name', $student->student_name) }}">
                @error('student_name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
