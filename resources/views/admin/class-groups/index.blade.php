@php $isSuperAdmin = session('admin_role') === 'super_admin'; @endphp
@extends('layouts.dashboard')

@section('title', 'Class Groups')
@section('dashboard_heading', 'Class Groups')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <p class="text-sm text-gray-600">@if($isSuperAdmin)Create class groups and assign them to examiners (lecturers). Examiners can then add students and create quizzes.@else Create class groups, attach courses, and manage student index lists. Quizzes belong to a class group and use its student list.@endif</p>
        @can('create', \App\Models\ClassGroup::class)
        <a href="{{ route('dashboard.class-groups.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Class Group
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
        @forelse($classGroups as $g)
            <div class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-primary-200 transition-all duration-200 text-left flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 group-hover:bg-primary-200 transition-colors">
                        <i class="fas fa-users text-lg"></i>
                    </span>
                    <div class="flex items-center gap-1" onclick="event.stopPropagation();">
                        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="p-1.5 rounded-md text-gray-400 hover:text-primary-600 hover:bg-primary-50" title="View"><i class="fas fa-eye text-sm"></i></a>
                        <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Edit"><i class="fas fa-pen text-sm"></i></a>
                        <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->name) }}\'? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-md text-gray-400 hover:text-danger-600 hover:bg-danger-50" title="Delete"><i class="fas fa-trash-alt text-sm"></i></button>
                        </form>
                    </div>
                </div>
                <a href="{{ route('dashboard.class-groups.show', $g) }}" class="flex-1 flex flex-col focus:outline-none focus:ring-0">
                    <h3 class="text-base font-semibold text-gray-900 truncate pr-2 group-hover:text-primary-600 transition-colors" title="{{ $g->name }}">{{ $g->name }}</h3>
                    @if($isSuperAdmin && $g->examiner)
                        <p class="mt-1 text-sm text-gray-500 truncate" title="{{ $g->examiner->name ?? $g->examiner->username }}">{{ $g->examiner->name ?? $g->examiner->username }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-3 text-sm text-gray-500">
                        <span class="inline-flex items-center gap-1"><i class="fas fa-user-graduate text-gray-400 w-4"></i>{{ $g->students_count ?? 0 }} students</span>
                        <span class="inline-flex items-center gap-1"><i class="fas fa-book text-gray-400 w-4"></i>{{ $g->courses_count ?? 0 }} courses</span>
                        <span class="inline-flex items-center gap-1"><i class="fas fa-clipboard-list text-gray-400 w-4"></i>{{ $g->quizzes_count ?? 0 }} quizzes</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-2">
                        <span class="text-xs text-primary-600 font-medium group-hover:underline">View details</span>
                        <i class="fas fa-arrow-right text-xs text-primary-500 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                </a>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4 rounded-xl border border-gray-200 bg-gray-50 p-10 text-center">
                <p class="text-gray-500">No class groups yet. Create one to get started.</p>
                @can('create', \App\Models\ClassGroup::class)
                <a href="{{ route('dashboard.class-groups.create') }}" class="mt-4 inline-flex items-center gap-2 text-primary-600 font-medium hover:underline">Create Class Group</a>
                @endcan
            </div>
        @endforelse
    </div>

    @if($classGroups->hasPages())
        <div class="mt-6">{{ $classGroups->links() }}</div>
    @endif
</div>
@endsection
