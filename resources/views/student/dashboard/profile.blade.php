@extends('layouts.student-dashboard')

@section('title', 'Profile')
@php $dashboardTitle = 'Profile'; @endphp

@section('dashboard_content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Profile</h1>
        <p class="text-gray-600 mt-1">Update your name. Phone is tied to your account for login.</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm max-w-md">
        <form action="{{ route('dashboard.my-profile.update') }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                <input type="text" id="index_number" value="{{ old('index_number', $student->index_number) }}" class="input w-full bg-gray-50" readonly disabled>
                <p class="text-xs text-gray-500 mt-1">Your index cannot be changed.</p>
            </div>
            <div>
                <label for="phone_display" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" id="phone_display" value="{{ $student->phone_contact ?: 'Not set' }}" class="input w-full bg-gray-50" readonly disabled>
                <p class="text-xs text-gray-500 mt-1">Phone is used for login codes and cannot be edited here.</p>
            </div>
            <div>
                <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Your name (optional)</label>
                <input type="text" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name) }}" placeholder="Full name" class="input w-full" maxlength="255" autocomplete="name" style="text-transform: capitalize;">
                @error('student_name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-action py-2.5 px-4 text-sm font-semibold">Save changes</button>
        </form>
    </div>

    @if(isset($classGroups) && $classGroups->isNotEmpty())
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm max-w-md">
        <h2 class="text-sm font-semibold text-gray-800 mb-2">My groups</h2>
        <p class="text-xs text-gray-500 mb-3">Your account is tied to these class groups (as created by your examiner).</p>
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">{{ $group->name }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
