@php $isSuperAdmin = session('admin_role') === 'super_admin'; @endphp
@extends('layouts.dashboard')

@section('title', 'Class Groups')
@section('dashboard_heading', 'Class Groups')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <p class="text-sm text-gray-600">@if($isSuperAdmin)Create class groups and assign them to examiners (lecturers). Examiners can then add students and create quizzes.@else Create class groups, attach courses, and manage student index lists. Quizzes belong to a class group and use its student list.@endif</p>
        <a href="{{ route('dashboard.class-groups.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Class Group
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 min-w-[600px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                    @if($isSuperAdmin)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Examiner</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Students</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Courses</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Quizzes</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($classGroups as $g)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-800"><i class="fas fa-users text-gray-400" title="Class group"></i>{{ $g->name }}</a>
                        </td>
                        @if($isSuperAdmin)
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $g->examiner?->name ?? $g->examiner?->username ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-4 text-sm text-gray-600">{{ $g->students_count ?? 0 }}</td>
                        <td class="px-4 py-4 text-sm text-gray-600">{{ $g->courses_count ?? 0 }}</td>
                        <td class="px-4 py-4 text-sm text-gray-600">{{ $g->quizzes_count ?? 0 }}</td>
                        <td class="px-4 py-4 text-right text-sm">
                            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-800 mr-3" title="View"><i class="fas fa-eye"></i> View</a>
                            <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-gray-800 mr-3" title="Edit {{ $g->name }}"><i class="fas fa-pen"></i> Edit</a>
                            <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->name) }}\'? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 text-danger-600 hover:text-danger-800 bg-transparent border-0 p-0 cursor-pointer text-sm font-medium" title="Delete {{ $g->name }}"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="px-4 py-8 text-center text-gray-500">No class groups yet. Create one to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($classGroups->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $classGroups->links() }}</div>
        @endif
    </div>
</div>
@endsection
