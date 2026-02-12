<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can manage departments.');
        }

        $faculties = Faculty::with('departments')->orderBy('name')->get();
        return view('admin.departments.index', compact('faculties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can create departments.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        Department::create([
            'name' => trim($request->name),
            'faculty_id' => $request->faculty_id,
        ]);

        return redirect()->route('dashboard.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can update departments.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->update([
            'name' => trim($request->name),
        ]);

        return redirect()->route('dashboard.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can delete departments.');
        }

        if ($department->users()->exists()) {
            return redirect()->route('dashboard.departments.index')
                ->with('error', 'Cannot delete department: it has users assigned. Reassign users first.');
        }

        $department->delete();
        return redirect()->route('dashboard.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Get departments for a specific faculty (AJAX endpoint)
     */
    public function getByFaculty(Faculty $faculty): JsonResponse
    {
        $departments = $faculty->departments()->orderBy('name')->get(['id', 'name']);
        return response()->json($departments);
    }
}
