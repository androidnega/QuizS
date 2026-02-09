@extends('layouts.dashboard')

@section('title', 'Edit Class Group')
@section('dashboard_heading', 'Edit Class Group')

@section('dashboard_content')
<div class="w-full max-w-2xl">
    @if(session('error'))
        <div class="alert alert-error mb-6">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php $isSuperAdmin = isset($examiners) && $examiners->isNotEmpty(); @endphp
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form action="{{ route('dashboard.class-groups.update', $classGroup) }}" method="post" class="space-y-6">
            @csrf
            @method('PUT')
            @if($isSuperAdmin)
            <div>
                <label for="examiner_id" class="block text-sm font-medium text-gray-700 mb-2">Assign to examiner (lecturer) *</label>
                <select name="examiner_id" id="examiner_id" required class="input w-full">
                    @foreach($examiners as $ex)
                        <option value="{{ $ex->id }}" {{ old('examiner_id', $classGroup->examiner_id) == $ex->id ? 'selected' : '' }}>{{ $ex->name ?: $ex->username }} ({{ $ex->username }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Reassign this class group to another examiner if needed.</p>
            </div>
            @endif
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Class Group Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $classGroup->name) }}" required maxlength="255" class="input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Attached courses</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @forelse($courses as $c)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ $classGroup->courses->contains($c) ? 'checked' : '' }}>
                            <span class="text-sm">{{ $c->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">No courses available.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
