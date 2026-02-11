@php
    $isSuperAdmin = session('admin_role') === 'super_admin';
    $groupedByLecturer = $classGroups->groupBy(fn ($g) => $g->examiner_id ?? 'unassigned');
@endphp
@extends('layouts.dashboard')

@section('title', 'Class Groups')
@section('dashboard_heading', 'Class Groups')

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-gray-600">@if($isSuperAdmin)Create class groups and assign them to examiners (lecturers). Examiners can then add students and create quizzes.@else Create class groups, attach courses, and manage student index lists. Quizzes belong to a class group and use its student list.@endif</p>
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

    @forelse($groupedByLecturer as $examinerId => $groups)
        @php
            $lecturer = $groups->first()->examiner;
            $lecturerLabel = $lecturer ? ($lecturer->name ?? $lecturer->username) : 'Unassigned';
        @endphp
        <section class="space-y-2">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $lecturerLabel }}</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 md:gap-3">
                @foreach($groups as $g)
                    <div class="group rounded-lg border border-slate-200 bg-white p-3 hover:border-slate-300 hover:bg-slate-50/50 transition-colors text-left flex flex-col min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 truncate group-hover:text-primary-600" title="{{ $g->name }}">{{ $g->name }}</h3>
                            </a>
                            <div class="flex items-center gap-0.5 shrink-0" onclick="event.stopPropagation();">
                                <a href="{{ route('dashboard.class-groups.show', $g) }}" class="p-1 rounded text-gray-400 hover:text-primary-600 hover:bg-primary-50" title="View"><i class="fas fa-eye text-xs"></i></a>
                                <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100" title="Edit"><i class="fas fa-pen text-xs"></i></a>
                                <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->name) }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-gray-400 hover:text-danger-600 hover:bg-danger-50" title="Delete"><i class="fas fa-trash-alt text-xs"></i></button>
                                </form>
                            </div>
                        </div>
                        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-500">
                            <span>{{ $g->students_count ?? 0 }} students</span>
                            <span>{{ $g->courses_count ?? 0 }} courses</span>
                            <span>{{ $g->quizzes_count ?? 0 }} quizzes</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
            <p class="text-sm text-gray-500">No class groups yet. Create one to get started.</p>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="mt-3 inline-flex text-sm text-primary-600 font-medium hover:underline">Create Class Group</a>
            @endcan
        </div>
    @endforelse

    @if($classGroups->hasPages())
        <div class="mt-4">{{ $classGroups->links() }}</div>
    @endif
</div>
@endsection
