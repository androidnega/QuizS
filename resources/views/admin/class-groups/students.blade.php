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

    <p class="text-sm text-gray-600">Manage student indices for this class group. This list is used for all quizzes in the group.</p>

    @if(!$isSuperAdmin)
    {{-- Add index --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add index</h2>
        <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="e.g. BC/ITS/24/047" class="input" value="{{ old('index_number') }}">
            </div>
            <div>
                <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                <input type="text" name="student_name" id="student_name" maxlength="255" class="input" value="{{ old('student_name') }}">
            </div>
            <button type="submit" class="btn btn-primary">Add index</button>
        </form>
    </div>

    {{-- Upload Excel --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Upload from file</h2>
        <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Excel / CSV file</label>
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
    </div>
    @else
    <p class="text-sm text-gray-500">Only examiners can add or remove indices for this class group.</p>
    @endif

    {{-- Table: all indices with View, Edit, Remove; Phone column --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">All indices ({{ $students->total() }})</h2>
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
                            $phone = $s->studentAccount?->phone_contact ?? null;
                            $displayName = $s->student_name ?? $s->studentAccount?->student_name ?? '—';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->index_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $displayName }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $phone ?: '—' }}</td>
                            @if(!$isSuperAdmin)
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-3">
                                        <button type="button" class="student-view-btn inline-flex items-center gap-1 text-gray-600 hover:text-primary-600 text-sm bg-transparent border-0 p-0 cursor-pointer" title="View info"
                                            data-index="{{ e($s->index_number) }}"
                                            data-name="{{ e($displayName) }}"
                                            data-phone="{{ e($phone ?? '') }}">
                                            <i class="fas fa-eye"></i> View
                                        </button>
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

    {{-- Modal: Student info --}}
    <div id="student-info-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50" aria-modal="true" role="dialog" aria-labelledby="student-info-modal-title">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h2 id="student-info-modal-title" class="text-lg font-semibold text-gray-900">Student info</h2>
                <button type="button" id="student-info-modal-close" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">&times;</button>
            </div>
            <div class="px-4 py-4 space-y-3 text-sm">
                <div>
                    <span class="font-medium text-gray-500 block">Index number</span>
                    <span id="modal-index" class="text-gray-900 font-mono"></span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 block">Name</span>
                    <span id="modal-name" class="text-gray-900"></span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 block">Phone</span>
                    <span id="modal-phone" class="text-gray-900"></span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        var modal = document.getElementById('student-info-modal');
        var closeBtn = document.getElementById('student-info-modal-close');
        var indexEl = document.getElementById('modal-index');
        var nameEl = document.getElementById('modal-name');
        var phoneEl = document.getElementById('modal-phone');
        if (!modal || !indexEl || !nameEl || !phoneEl) return;
        function openModal(index, name, phone) {
            indexEl.textContent = index || '—';
            nameEl.textContent = name || '—';
            phoneEl.textContent = phone || '—';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
        document.querySelectorAll('.student-view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openModal(this.getAttribute('data-index'), this.getAttribute('data-name'), this.getAttribute('data-phone'));
            });
        });
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('flex')) closeModal();
        });
    })();
    </script>
    @endpush
</div>
@endsection
