@extends('layouts.dashboard')

@section('title', 'Institution')
@section('dashboard_heading', 'Institution / School')

@push('styles')
<style>
    .institution-page .institution-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        padding: 1.5rem 1.75rem;
    }
    .institution-page .institution-card .form-group {
        margin-bottom: 1.25rem;
    }
    .institution-page .institution-card .form-group:last-of-type { margin-bottom: 0; }
    .institution-page label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
    }
    .institution-page input[type="text"] {
        width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #111827;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        min-height: 44px;
    }
    .institution-page input[type="text"]:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .institution-page input[type="file"] {
        width: 100%;
        padding: 0.5rem 0;
        font-size: 0.875rem;
        color: #374151;
    }
    .institution-page .help {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .institution-page .logo-preview {
        display: inline-block;
        height: 40px;
        max-width: 140px;
        object-fit: contain;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-top: 0.25rem;
    }
    .institution-page .btn-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.25rem;
        min-height: 44px;
        font-size: 0.9375rem;
        font-weight: 600;
        color: #fff;
        background: #2563eb;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .institution-page .btn-save:hover {
        background: #1d4ed8;
    }
    .institution-page .alert-box {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .institution-page .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .institution-page .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
@endpush

@section('dashboard_content')
<div class="institution-page w-full">
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
        <div class="alert-box alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-box alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-box alert-error">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.institution.update') }}" method="post" class="institution-card" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="institution_name">Institution / School name</label>
            <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $institution_name ?? '') }}" placeholder="e.g. Takoradi Technical University">
            <p class="help">Shown on PDF score reports. Leave blank to omit.</p>
        </div>
        <div class="form-group">
            <label for="institution_logo">Institution logo</label>
            @if(!empty($institution_logo))
                <p class="help mb-1">Current:</p>
                @if(str_starts_with($institution_logo, 'http'))
                    <img src="{{ $institution_logo }}" alt="Logo" class="logo-preview">
                @else
                    <img src="{{ asset('storage/' . $institution_logo) }}" alt="Logo" class="logo-preview">
                @endif
                <p class="help mt-1">Upload new to replace (goes to Cloudinary)</p>
            @endif
            <input type="file" name="institution_logo" id="institution_logo" accept="image/*">
            <p class="help">Image for PDF header. Max 2MB. Uploaded directly to Cloudinary.</p>
        </div>
        <div class="flex justify-end" style="margin-top: 1.5rem;">
            <button type="submit" class="btn-save">Save</button>
        </div>
    </form>
</div>
@endsection
