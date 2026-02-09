@extends('layouts.dashboard')

@section('title', 'Institution')
@section('dashboard_heading', 'Institution / School')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Institution</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Institution / School</h1>
        <p class="text-gray-600 mt-1">Name and logo shown on PDF score reports. Logo is uploaded directly to Cloudinary.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.institution.update') }}" method="post" class="card space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div>
            <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-1">Institution / School name</label>
            <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $institution_name ?? '') }}" class="input w-full" placeholder="e.g. Takoradi Technical University">
            <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports. Leave blank to omit.</p>
        </div>
        <div>
            <label for="institution_logo" class="block text-sm font-medium text-gray-700 mb-1">Institution logo</label>
            @if(!empty($institution_logo))
                <p class="text-xs text-gray-500 mb-1">Current:</p>
                @if(str_starts_with($institution_logo, 'http'))
                    <img src="{{ $institution_logo }}" alt="Logo" class="inline-block h-10 max-w-[140px] object-contain border border-gray-200 rounded">
                @else
                    <img src="{{ asset('storage/' . $institution_logo) }}" alt="Logo" class="inline-block h-10 max-w-[140px] object-contain border border-gray-200 rounded">
                @endif
                <p class="text-xs text-gray-500 mt-1">— upload new to replace (goes to Cloudinary)</p>
            @endif
            <input type="file" name="institution_logo" id="institution_logo" accept="image/*" class="input w-full text-sm mt-2">
            <p class="text-xs text-gray-500 mt-1">Image for PDF header. Max 2MB. Uploaded directly to Cloudinary.</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
