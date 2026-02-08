@extends('layouts.dashboard')

@section('title', 'Courses')
@section('dashboard_heading', 'Courses')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-2 text-sm text-gray-600">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 font-medium">Courses</span>
            </div>
            <a href="{{ route('dashboard.courses.create') }}" class="btn btn-primary">Add course</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quizzes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examiners</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($courses as $c)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $c->code ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $c->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $c->quizzes_count ?? 0 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @if($c->examiners->isNotEmpty())
                                        {{ $c->examiners->pluck('username')->join(', ') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($c->is_archived)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">Archived</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">Active</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('dashboard.courses.edit', $c) }}" class="text-primary-600 hover:text-primary-900 mr-3">Edit</a>
                                    @if($c->is_archived)
                                        <form action="{{ route('dashboard.courses.unarchive', $c) }}" method="post" class="inline">
                                            @csrf
                                            <button type="submit" class="text-success-600 hover:text-success-900 mr-3">Restore</button>
                                        </form>
                                    @else
                                        <form action="{{ route('dashboard.courses.archive', $c) }}" method="post" class="inline mr-3" onsubmit="return confirm('Archive this course?');">
                                            @csrf
                                            <button type="submit" class="text-gray-600 hover:text-gray-900">Archive</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('dashboard.courses.destroy', $c) }}" method="post" class="inline" onsubmit="return confirm('Permanently delete course \'{{ addslashes($c->name) }}\'? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900" title="Delete course">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No courses yet. Create one to assign examiners and create quizzes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>
@endsection
