<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Setting;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    use InteractsWithAdminSession;

    /**
     * Institution / School settings (examiner-accessible). Used on PDF score reports.
     */
    public function index(): View
    {
        $institutionName = Setting::getValue(Setting::KEY_INSTITUTION_NAME, '');
        $institutionLogo = Setting::getValue(Setting::KEY_INSTITUTION_LOGO, '');
        return view('admin.institution.index', [
            'institution_name' => $institutionName,
            'institution_logo' => $institutionLogo,
        ]);
    }

    /**
     * Update institution name and/or logo. Logo uploads directly to Cloudinary.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'institution_name' => 'nullable|string|max:255',
            'institution_logo' => 'nullable|image|max:2048',
        ]);

        Setting::setValue(Setting::KEY_INSTITUTION_NAME, $request->filled('institution_name') ? trim($request->institution_name) : null);

        if ($request->hasFile('institution_logo')) {
            $url = CloudinaryService::uploadFromFile($request->file('institution_logo'));
            if ($url) {
                Setting::setValue(Setting::KEY_INSTITUTION_LOGO, $url);
            } else {
                return redirect()->route('dashboard.institution.index')
                    ->with('error', 'Logo upload failed. Ensure Cloudinary is configured in Admin Settings.');
            }
        }

        return redirect()->route('dashboard.institution.index')->with('success', 'Institution settings saved.');
    }
}
