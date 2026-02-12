@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@extends('layouts.dashboard')

@section('title', 'Student indices — ' . $classGroup->name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Student index list</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Back to class group --}}
    <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600">
        <i class="fas fa-arrow-left"></i> Back to {{ $classGroup->name }}
    </a>

    <p class="text-sm text-gray-600 mb-4">Manage student indices for this class group. This list is used for all quizzes in the group.</p>

    @if(!$isSuperAdmin)
    {{-- Add index + Upload: compact single card --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <span class="text-sm font-semibold text-gray-700 mr-1">Add index</span>
            <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label for="index_number" class="sr-only">Index number</label>
                    <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="Index (e.g. BC/ITS/24/047)" class="input min-h-0 py-1.5 px-2.5 text-sm min-w-[140px]" value="{{ old('index_number') }}">
                </div>
                <div>
                    <label for="student_name" class="sr-only">Name</label>
                    <input type="text" name="student_name" id="student_name" maxlength="255" placeholder="Name (optional)" class="input min-h-0 py-1.5 px-2.5 text-sm min-w-[120px]" value="{{ old('student_name') }}">
                </div>
                <button type="submit" class="rounded-md bg-primary-600 px-2.5 py-1.5 text-sm font-medium text-white hover:bg-primary-700">Add</button>
            </form>
        </div>
        <div class="border-t border-gray-100 pt-3 flex flex-wrap items-end gap-2">
            <span class="text-sm font-semibold text-gray-700 mr-1">Upload from file</span>
            <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label for="file" class="sr-only">File</label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="text-sm file:mr-2 file:py-1 file:px-2 file:rounded file:border file:border-gray-300 file:text-sm">
                </div>
                <div>
                    <label for="upload_mode" class="sr-only">Mode</label>
                    <select name="upload_mode" id="upload_mode" required class="input min-h-0 py-1.5 px-2 text-sm">
                        <option value="replace">Replace list</option>
                        <option value="merge">Merge</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Upload</button>
            </form>
        </div>
    </div>
    @else
    <p class="text-sm text-gray-500">Only examiners can add or remove indices for this class group.</p>
    @endif

    {{-- Table: all indices with View, Edit, Remove; Phone column --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">All indices ({{ $students->total() }})</h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard.class-groups.students.export.excel', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
                <a href="{{ route('dashboard.class-groups.students.export.pdf', $classGroup) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 hover:border-gray-300" download>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <form method="get" action="{{ route('dashboard.class-groups.students.index', $classGroup) }}" id="student-search-form" class="flex items-center gap-2">
                    <label for="student-search" class="sr-only">Search</label>
                    <input type="search" name="search" id="student-search" value="{{ old('search', $search ?? '') }}" placeholder="Search index, name, phone…" class="input min-h-0 py-1.5 px-2.5 text-sm w-48 max-w-full" autocomplete="off">
                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Search</button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 min-w-[500px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Index</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Phone</th>
                        @if(!$isSuperAdmin)
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($students as $s)
                        @php
                            // Get phone number from student account if available
                            $phone = $s->studentAccount?->phone_contact ?? null;
                            $phone = $phone && trim($phone) !== '' ? trim($phone) : null;
                            
                            // Priority: student account name > class group name > "—"
                            $displayName = $s->studentAccount?->student_name ?? $s->student_name ?? null;
                            $displayName = $displayName && trim($displayName) !== '' ? trim($displayName) : '—';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->index_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $displayName }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $phone ?? '—' }}</td>
                            @if(!$isSuperAdmin)
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-3">
                                        <a href="{{ route('dashboard.class-groups.students.show', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-primary-600 text-sm" title="View details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('dashboard.class-groups.students.edit', [$classGroup, $s]) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 text-sm" title="Edit"><i class="fas fa-pen"></i> Edit</a>
                                        <form action="{{ route('dashboard.class-groups.students.destroy', [$classGroup, $s]) }}" method="post" class="inline" onsubmit="return confirm('Remove this index from the group?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-danger-600 hover:text-danger-800 text-sm bg-transparent border-0 p-0 cursor-pointer" title="Remove"><i class="fas fa-trash-alt"></i> Remove</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 3 : 4 }}" class="px-4 py-8 text-center text-gray-500 text-sm">No students yet.@if(!$isSuperAdmin) Add indices above or upload Excel/CSV.@endif</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">{{ $students->links() }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
    (function() {
        var searchInput = document.getElementById('student-search');
        var searchForm = document.getElementById('student-search-form');
        if (searchInput && searchForm) {
            var debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    searchForm.submit();
                }, 350);
            });
        }
    })();
    </script>
    @endpush
</div>
@endsection
