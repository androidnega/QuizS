@php $isSuperAdmin = session('admin_role') === 'super_admin'; @endphp
@extends('layouts.dashboard')

@section('title', $classGroup->name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-users text-primary-600"></i>{{ $classGroup->name }}</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Back link --}}
    <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600">
        <i class="fas fa-arrow-left"></i> Back to class groups
    </a>

    {{-- Card: Actions (compact) --}}
    <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-gray-700 mr-1">Group actions</span>
            <a href="{{ route('dashboard.class-groups.edit', $classGroup) }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" title="Edit {{ $classGroup->name }}"><i class="fas fa-pen text-xs"></i> Edit</a>
            <form action="{{ route('dashboard.class-groups.destroy', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($classGroup->name) }}\'? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-danger-300 bg-danger-50 px-2.5 py-1.5 text-sm font-medium text-danger-700 hover:bg-danger-100" title="Delete {{ $classGroup->name }}"><i class="fas fa-trash-alt text-xs"></i> Delete</button>
            </form>
            @if(!$isSuperAdmin)
                @if($students->total() > 0)
                    <a href="{{ route('dashboard.quizzes.create') }}?class_group_id={{ $classGroup->id }}" class="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-primary-700"><i class="fas fa-plus text-xs"></i> Create quiz</a>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-primary-200 px-2.5 py-1.5 text-sm font-medium text-primary-800 opacity-70 cursor-not-allowed" title="Add at least one student first"><i class="fas fa-plus text-xs"></i> Create quiz</span>
                @endif
            @endif
        </div>
        @if(!$isSuperAdmin && $students->total() === 0)
            <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1.5"><strong>No students yet.</strong> Add indices in Student index list below before creating a quiz.</p>
        @endif
    </div>

    {{-- Grid: Courses + Quizzes cards --}}
    <div class="grid md:grid-cols-2 gap-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2"><i class="fas fa-book text-primary-600"></i> Attached courses</h2>
            @if($classGroup->courses->isEmpty())
                <p class="text-sm text-gray-500">No courses attached. Edit the class group to attach courses.</p>
            @else
                <ul class="space-y-2">
                    @foreach($classGroup->courses as $c)
                        <li class="text-sm text-gray-700 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-primary-400"></span>{{ $c->name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2"><i class="fas fa-clipboard-list text-primary-600"></i> Quizzes</h2>
            <p class="text-sm text-gray-600 mb-4">{{ $classGroup->quizzes->count() }} quiz(zes) in this class group.</p>
            @if($classGroup->quizzes->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($classGroup->quizzes->take(5) as $q)
                        <li><a href="{{ route('dashboard.quizzes.show', $q) }}" class="text-primary-600 hover:underline text-sm flex items-center gap-2"><i class="fas fa-external-link-alt text-xs"></i>{{ $q->title }}</a></li>
                    @endforeach
                    @if($classGroup->quizzes->count() > 5)
                        <li class="text-sm text-gray-500">… and {{ $classGroup->quizzes->count() - 5 }} more</li>
                    @endif
                </ul>
            @else
                <p class="text-sm text-gray-500">No quizzes yet. Create one from the action above once students are added.</p>
            @endif
        </div>
    </div>

    {{-- Card: Student index list — link to full management page --}}
    <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="block rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:border-primary-300 hover:shadow-md transition-all group">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2 group-hover:text-primary-600">
                    <i class="fas fa-user-graduate text-primary-600"></i> Student index list
                </h2>
                <p class="text-sm text-gray-600">Manage student indices for this class group. Add, edit, remove, or upload from Excel. This list is used for all quizzes in this group.</p>
                <p class="text-sm font-medium text-primary-600 mt-3 group-hover:underline">Open and manage indices →</p>
            </div>
            <div class="flex-shrink-0 rounded-lg bg-primary-50 px-4 py-2 text-center">
                <span class="text-2xl font-bold tabular-nums text-primary-700">{{ $students->total() }}</span>
                <span class="block text-xs font-medium text-primary-600">indices</span>
            </div>
        </div>
    </a>
</div>
@endsection
