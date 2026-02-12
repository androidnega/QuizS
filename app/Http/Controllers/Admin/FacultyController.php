<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Faculty;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacultyController extends Controller
{
    use InteractsWithAdminSession;

    public function index(): View
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can manage faculties.');
        }

        $institutions = Institution::with('faculties')->orderBy('name')->get();
        return view('admin.faculties.index', compact('institutions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can create faculties.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
        ]);

        Faculty::create([
            'name' => trim($request->name),
            'institution_id' => $request->institution_id,
        ]);

        return redirect()->route('dashboard.faculties.index')
            ->with('success', 'Faculty created successfully.');
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can update faculties.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $faculty->update([
            'name' => trim($request->name),
        ]);

        return redirect()->route('dashboard.faculties.index')
            ->with('success', 'Faculty updated successfully.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can delete faculties.');
        }

        if ($faculty->departments()->exists()) {
            return redirect()->route('dashboard.faculties.index')
                ->with('error', 'Cannot delete faculty: it has departments. Delete or reassign departments first.');
        }

        $faculty->delete();
        return redirect()->route('dashboard.faculties.index')
            ->with('success', 'Faculty deleted successfully.');
    }
}
