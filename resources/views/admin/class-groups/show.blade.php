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

    <div class="flex flex-wrap items-center gap-4">
        <a href="{{ route('dashboard.class-groups.edit', $classGroup) }}" class="btn btn-secondary inline-flex items-center gap-2" title="Edit {{ $classGroup->name }}"><i class="fas fa-pen"></i> Edit class group</a>
        <form action="{{ route('dashboard.class-groups.destroy', $classGroup) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($classGroup->name) }}\'? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn border border-danger-300 bg-danger-50 text-danger-700 hover:bg-danger-100 inline-flex items-center gap-2" title="Delete {{ $classGroup->name }}"><i class="fas fa-trash-alt"></i> Delete group</button>
        </form>
        @if(!$isSuperAdmin)
            @if($students->total() > 0)
                <a href="{{ route('dashboard.quizzes.create') }}?class_group_id={{ $classGroup->id }}" class="btn btn-primary inline-flex items-center gap-2"><i class="fas fa-plus"></i> Create quiz for this group</a>
            @else
                <span class="btn btn-primary opacity-60 cursor-not-allowed inline-flex items-center gap-2" title="Add at least one student to this class group before creating a quiz"><i class="fas fa-plus"></i> Create quiz for this group</span>
            @endif
        @endif
    </div>
    @if(!$isSuperAdmin && $students->total() === 0)
        <div class="alert alert-warning">
            <strong>No students yet.</strong> Add at least one student index (or upload Excel/CSV) before you can create a quiz for this class group.
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Attached courses</h2>
            @if($classGroup->courses->isEmpty())
                <p class="text-sm text-gray-500">No courses attached. Edit the class group to attach courses.</p>
            @else
                <ul class="space-y-2">
                    @foreach($classGroup->courses as $c)
                        <li class="text-sm text-gray-700">{{ $c->name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Quizzes</h2>
            <p class="text-sm text-gray-600 mb-4">{{ $classGroup->quizzes->count() }} quiz(zes) in this class group.</p>
            @if($classGroup->quizzes->isNotEmpty())
                <ul class="space-y-1">
                    @foreach($classGroup->quizzes->take(5) as $q)
                        <li><a href="{{ route('dashboard.quizzes.show', $q) }}" class="text-primary-600 hover:underline text-sm">{{ $q->title }}</a></li>
                    @endforeach
                    @if($classGroup->quizzes->count() > 5)
                        <li class="text-sm text-gray-500">… and {{ $classGroup->quizzes->count() - 5 }} more</li>
                    @endif
                </ul>
            @endif
        </div>
    </div>

    <section class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Student index list</h2>
            @if($isSuperAdmin)
                <p class="text-sm text-gray-600 mb-4">Admin cannot upload or manage students. Only examiners can add/remove indices per class group.</p>
            @else
                <p class="text-sm text-gray-600 mb-4">This list is used for all quizzes in this class group. Add indices below or upload Excel/CSV (replace or merge).</p>
                <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="flex flex-wrap items-end gap-4 mb-6">
                    @csrf
                    <div>
                        <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                        <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="e.g. BC/ITS/24/047" class="input">
                    </div>
                    <div>
                        <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                        <input type="text" name="student_name" id="student_name" maxlength="255" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary">Add index</button>
                </form>
                <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Excel file</label>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="input file:mr-2 file:py-2 file:px-3 file:rounded file:border file:border-primary-300 file:bg-primary-50 file:text-primary-700">
                    </div>
                    <div>
                        <label for="upload_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                        <select name="upload_mode" id="upload_mode" required class="input">
                            <option value="replace">Replace — clear list, then add from file</option>
                            <option value="merge">Merge — add/update from file</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Upload</button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 min-w-[400px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Index</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                        @if(!$isSuperAdmin)
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($students as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $s->index_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $s->student_name ?? '-' }}</td>
                            @if(!$isSuperAdmin)
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $s]) }}" method="post" class="inline" onsubmit="return confirm('Remove this index?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 text-danger-600 hover:text-danger-800 text-sm bg-transparent border-0 p-0 cursor-pointer" title="Remove"><i class="fas fa-trash-alt"></i> Remove</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 2 : 3 }}" class="px-4 py-6 text-center text-gray-500 text-sm">No students yet.@if(!$isSuperAdmin) Add indices or upload Excel.@endif</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($students->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $students->links() }}</div>
            @endif
        </div>
    </section>
</div>
@endsection
