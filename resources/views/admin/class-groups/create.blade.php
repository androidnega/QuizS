@extends('layouts.examiner')

@section('title', 'Create Class Group')
@section('examiner_heading', 'Create Class Group')

@section('examiner_content')
<div class="w-full">
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

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-sm text-gray-600 mb-6">Create a class group (e.g. BTECH GROUP A L100). @if(isset($examiners) && $examiners->isNotEmpty()) Assign an examiner (lecturer) and attach courses. @else Attach courses assigned to you and add the student index list. @endif Quizzes will use this list.</p>

        <form action="{{ route('dashboard.class-groups.store') }}" method="post" class="space-y-6">
            @csrf
            @if(isset($examiners) && $examiners->isNotEmpty())
            <div>
                <label for="examiner_id" class="block text-sm font-medium text-gray-700 mb-2">Assign to examiner (lecturer) *</label>
                <select name="examiner_id" id="examiner_id" required class="input w-full">
                    <option value="">— Select examiner —</option>
                    @foreach($examiners as $ex)
                        <option value="{{ $ex->id }}" {{ old('examiner_id') == $ex->id ? 'selected' : '' }}>{{ $ex->name ?: $ex->username }} ({{ $ex->username }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">The examiner will own this class group and can add students and create quizzes.</p>
            </div>
            @elseif(isset($examiners) && $examiners->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-medium">No examiners yet.</p>
                <p class="mt-1">Add examiner accounts in <a href="{{ route('dashboard.users.index') }}" class="underline font-medium">User management</a> first, then you can create class groups and assign them.</p>
            </div>
            @endif
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Class Group Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. BTECH GROUP A L100" class="input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Attach courses (optional)</label>
                <p class="text-xs text-gray-500 mb-2">@if(isset($examiners) && $examiners->isNotEmpty()) Attach courses for this class group. @else Only courses assigned to you can be attached. @endif You can change this later.</p>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @forelse($courses as $c)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" {{ in_array($c->id, old('course_ids', [])) ? 'checked' : '' }}>
                            <span class="text-sm">{{ $c->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">No courses assigned to you. Ask Super Admin to assign courses.</p>
                    @endforelse
                </div>
            </div>
            <div class="flex gap-3">
                @if(isset($examiners) && $examiners->isEmpty())
                    <button type="button" disabled class="btn btn-primary opacity-60 cursor-not-allowed">Create Class Group</button>
                @else
                    <button type="submit" class="btn btn-primary">Create Class Group</button>
                @endif
                <a href="{{ route('dashboard.class-groups.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
