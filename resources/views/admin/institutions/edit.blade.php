@extends('layouts.dashboard')

@section('title', 'Edit institution')
@section('dashboard_heading', 'Edit institution')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.institutions.index') }}" class="hover:text-primary-600">Institutions</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">{{ $institution->name }}</span>
    </div>

    <div class="card p-6 max-w-lg">
        @if(session('error'))
            <div class="rounded-lg bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 text-sm mb-4">{{ session('error') }}</div>
        @endif

        <form action="{{ route('dashboard.institutions.update', $institution) }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Institution name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $institution->name) }}" required class="input w-full @error('name') border-danger-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region (optional)</label>
                <input type="text" name="region" id="region" value="{{ old('region', $institution->region) }}" class="input w-full @error('region') border-danger-500 @enderror" placeholder="e.g. Greater Accra Region">
                @error('region')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($institution->logo_url)
                    <p class="text-xs text-gray-500 mb-1">Current:</p>
                    <img src="{{ $institution->logo_url }}" alt="{{ $institution->name }}" class="h-12 object-contain rounded border border-gray-200 bg-white mb-2">
                @endif
                <input type="file" name="logo" id="logo" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-1 text-xs text-gray-500">Max 2MB. Uploaded to Cloudinary. Shown in examiner sidebar.</p>
                @error('logo')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('dashboard.institutions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
