@php
    $isSuperAdmin = session('admin_role') === 'super_admin';
@endphp
@extends('layouts.dashboard')

@section('title', 'Class Groups')
@section('dashboard_heading', 'Class Groups')

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-gray-600">@if($isSuperAdmin)Class groups by lecturer. Expand a lecturer to see their groups and courses.@else Your class groups and assigned courses.@endif</p>
        @can('create', \App\Models\ClassGroup::class)
        <a href="{{ route('dashboard.class-groups.create') }}" class="btn btn-primary text-sm">Add class group</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error text-sm">{{ session('error') }}</div>
    @endif

    {{-- Unassigned groups (no lecturer) --}}
    @if($unassignedGroups->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <details class="group/details" open>
                <summary class="flex items-center justify-between cursor-pointer list-none px-4 py-3 bg-gray-50 hover:bg-gray-100 text-sm font-semibold text-gray-700">
                    <span>Unassigned</span>
                    <span class="text-gray-500 font-normal">{{ $unassignedGroups->count() }} group(s)</span>
                </summary>
                <div class="p-3 bg-white border-t border-gray-200">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                        @foreach($unassignedGroups as $g)
                            @include('admin.class-groups.partials.group-card', ['g' => $g])
                        @endforeach
                    </div>
                </div>
            </details>
        </section>
    @endif

    {{-- Lecturers with groups --}}
    @forelse($lecturers as $lecturer)
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <details class="group/details">
                <summary class="flex items-center justify-between cursor-pointer list-none px-4 py-3 bg-slate-50 hover:bg-slate-100 text-sm font-semibold text-gray-800">
                    <span>{{ $lecturer->name ?? $lecturer->username }}</span>
                    <span class="text-gray-500 font-normal">{{ $lecturer->class_groups_count ?? $lecturer->classGroups->count() }} group(s) · {{ $lecturer->courses->count() }} course(s)</span>
                </summary>
                <div class="p-3 bg-white border-t border-gray-200 space-y-4">
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Class groups</h4>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                            @foreach($lecturer->classGroups as $g)
                                @include('admin.class-groups.partials.group-card', ['g' => $g])
                            @endforeach
                        </div>
                    </div>
                    @if($lecturer->courses->isNotEmpty())
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Assigned courses</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($lecturer->courses as $c)
                                    <a href="{{ route('dashboard.courses.edit', $c) }}" class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">{{ $c->code ?? $c->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </details>
        </section>
    @empty
        @if($unassignedGroups->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                <p class="text-sm text-gray-500">No class groups yet. Create one to get started.</p>
                @can('create', \App\Models\ClassGroup::class)
                <a href="{{ route('dashboard.class-groups.create') }}" class="mt-3 inline-flex text-sm text-primary-600 font-medium hover:underline">Create Class Group</a>
                @endcan
            </div>
        @endif
    @endforelse

    @if($lecturers->hasPages())
        <div class="mt-4">{{ $lecturers->links() }}</div>
    @endif
</div>
@endsection
