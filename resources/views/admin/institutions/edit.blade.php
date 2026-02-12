@extends('layouts.dashboard')

@section('title', 'Edit institution')
@section('dashboard_heading', 'Edit institution')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.institutions.index') }}" class="hover:text-primary-600">Institutions</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">{{ $institution->name }}</span>
    </div>

    <div class="card p-6 max-w-lg">
        @if(session('error'))
            <div class="rounded-lg bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 text-sm mb-4">{{ session('error') }}</div>
        @endif

        <form action="{{ route('dashboard.institutions.update', $institution) }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Institution name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $institution->name) }}" required class="input w-full @error('name') border-danger-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region (optional)</label>
                <input type="text" name="region" id="region" value="{{ old('region', $institution->region) }}" class="input w-full @error('region') border-danger-500 @enderror" placeholder="e.g. Greater Accra Region">
                @error('region')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($institution->logo_url)
                    <p class="text-xs text-gray-500 mb-1">Current:</p>
                    <img src="{{ $institution->logo_url }}" alt="{{ $institution->name }}" class="h-12 object-contain rounded border border-gray-200 bg-white mb-2">
                @endif
                <input type="file" name="logo" id="logo" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-1 text-xs text-gray-500">Max 2MB. Uploaded to Cloudinary. Shown in examiner sidebar.</p>
                @error('logo')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('dashboard.institutions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Faculties and Departments Management --}}
    <div class="card p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Faculties and Departments</h2>
        
        {{-- Faculties Section --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-700">Faculties</h3>
                <button type="button" onclick="openAddFacultyModal()" class="btn btn-sm btn-primary">Add Faculty</button>
            </div>
            <div id="faculties-list" class="space-y-2">
                @forelse($institution->faculties as $faculty)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg" data-faculty-id="{{ $faculty->id }}">
                        <div class="flex-1">
                            <span class="font-medium text-gray-900">{{ $faculty->name }}</span>
                            <span class="text-xs text-gray-500 ml-2">({{ $faculty->departments->count() }} departments)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openAddDepartmentModal({{ $faculty->id }}, '{{ $faculty->name }}')" class="text-xs px-2 py-1 text-primary-600 hover:bg-primary-50 rounded">Add Department</button>
                            <button type="button" onclick="deleteFaculty({{ $faculty->id }})" class="text-xs px-2 py-1 text-danger-600 hover:bg-danger-50 rounded">Delete</button>
                        </div>
                        @if($faculty->departments->isNotEmpty())
                            <div class="w-full mt-2 pt-2 border-t border-gray-100">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($faculty->departments as $dept)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">
                                            {{ $dept->name }}
                                            <button type="button" onclick="deleteDepartment({{ $dept->id }})" class="ml-1.5 text-danger-600 hover:text-danger-800">×</button>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-2">No faculties added yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Add Faculty Modal --}}
<div id="addFacultyModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Add Faculty</h2>
            <button type="button" onclick="closeAddFacultyModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="addFacultyForm" class="p-6 space-y-4">
            <div>
                <label for="faculty_name" class="block text-sm font-medium text-gray-700 mb-1">Faculty Name</label>
                <input type="text" id="faculty_name" name="name" required class="input w-full" placeholder="e.g. Faculty of Engineering">
            </div>
            <input type="hidden" id="faculty_institution_id" value="{{ $institution->id }}">
            <div id="facultyError" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary flex-1">Add Faculty</button>
                <button type="button" onclick="closeAddFacultyModal()" class="btn btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Department Modal --}}
<div id="addDepartmentModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Add Department</h2>
            <button type="button" onclick="closeAddDepartmentModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="addDepartmentForm" class="p-6 space-y-4">
            <div>
                <p class="text-sm text-gray-600 mb-2">Faculty: <strong id="department_faculty_name"></strong></p>
            </div>
            <div>
                <label for="department_name" class="block text-sm font-medium text-gray-700 mb-1">Department Name</label>
                <input type="text" id="department_name" name="name" required class="input w-full" placeholder="e.g. Computer Science">
            </div>
            <input type="hidden" id="department_faculty_id" name="faculty_id">
            <div id="departmentError" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary flex-1">Add Department</button>
                <button type="button" onclick="closeAddDepartmentModal()" class="btn btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const institutionId = {{ $institution->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function openAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.remove('hidden');
    document.getElementById('addFacultyModal').classList.add('flex');
    document.getElementById('faculty_name').focus();
}

function closeAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.add('hidden');
    document.getElementById('addFacultyModal').classList.remove('flex');
    document.getElementById('addFacultyForm').reset();
    document.getElementById('facultyError').classList.add('hidden');
}

function openAddDepartmentModal(facultyId, facultyName) {
    document.getElementById('addDepartmentModal').classList.remove('hidden');
    document.getElementById('addDepartmentModal').classList.add('flex');
    document.getElementById('department_faculty_id').value = facultyId;
    document.getElementById('department_faculty_name').textContent = facultyName;
    document.getElementById('department_name').focus();
}

function closeAddDepartmentModal() {
    document.getElementById('addDepartmentModal').classList.add('hidden');
    document.getElementById('addDepartmentModal').classList.remove('flex');
    document.getElementById('addDepartmentForm').reset();
    document.getElementById('departmentError').classList.add('hidden');
}

document.getElementById('addFacultyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('faculty_name').value.trim();
    const errorEl = document.getElementById('facultyError');
    
    errorEl.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route("dashboard.faculties.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                name: name,
                institution_id: institutionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            errorEl.textContent = data.message || 'Failed to add faculty';
            errorEl.classList.remove('hidden');
        }
    } catch (error) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    }
});

document.getElementById('addDepartmentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('department_name').value.trim();
    const facultyId = document.getElementById('department_faculty_id').value;
    const errorEl = document.getElementById('departmentError');
    
    errorEl.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route("dashboard.departments.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                name: name,
                faculty_id: facultyId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            errorEl.textContent = data.message || 'Failed to add department';
            errorEl.classList.remove('hidden');
        }
    } catch (error) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    }
});

async function deleteFaculty(facultyId) {
    if (!confirm('Delete this faculty? All departments under it will also be deleted.')) return;
    
    try {
        const response = await fetch(`{{ route('dashboard.faculties.destroy', '') }}/${facultyId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete faculty');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

async function deleteDepartment(departmentId) {
    if (!confirm('Delete this department?')) return;
    
    try {
        const response = await fetch(`{{ route('dashboard.departments.destroy', '') }}/${departmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete department');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}
</script>
@endpush
@endsection
