<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AiQuestionService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show settings page (general, email, AI).
     */
    public function index(): View
    {
        $geminiKey = Setting::getValue(Setting::KEY_GEMINI_API);
        $deepseekKey = Setting::getValue(Setting::KEY_DEEPSEEK_API);
        $geminiKeyMasked = $geminiKey ? substr($geminiKey, 0, 8) . '…' . substr($geminiKey, -4) : null;
        $deepseekKeyMasked = $deepseekKey ? substr($deepseekKey, 0, 8) . '…' . substr($deepseekKey, -4) : null;

        return view('admin.settings.index', [
            'gemini_key_set' => (bool) $geminiKey,
            'gemini_key_masked' => $geminiKeyMasked,
            'deepseek_key_set' => (bool) $deepseekKey,
            'deepseek_key_masked' => $deepseekKeyMasked,
            'app_name' => Setting::getValue(Setting::KEY_APP_NAME, config('app.name')),
            'app_timezone' => Setting::getValue(Setting::KEY_APP_TIMEZONE, config('app.timezone', 'UTC')),
            'mail_mailer' => Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default') ?? 'smtp'),
            'mail_host' => Setting::getValue(Setting::KEY_MAIL_HOST, config('mail.mailers.smtp.host') ?? ''),
            'mail_port' => Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? 587)),
            'mail_username' => Setting::getValue(Setting::KEY_MAIL_USERNAME, ''),
            'mail_encryption' => Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, config('mail.mailers.smtp.encryption') ?? 'tls'),
            'mail_from_address' => Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address') ?? ''),
            'mail_from_name' => Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name') ?? ''),
            'notify_result_ready' => Setting::getValue(Setting::KEY_NOTIFY_RESULT_READY, '0') === '1',
            'notify_result_email' => Setting::getValue(Setting::KEY_NOTIFY_RESULT_EMAIL, ''),
            'cloudinary_cloud_name' => Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME, ''),
            'cloudinary_key_set' => (bool) Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY),
            'cloudinary_key_masked' => ($k = Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY)) ? (strlen($k) > 8 ? substr($k, 0, 4) . '…' . substr($k, -4) : '••••') : null,
            'cloudinary_secret_set' => (bool) Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET),
            'cloudinary_folder' => Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap'),
            'lock_examiner_create_group' => Setting::getValue(Setting::KEY_LOCK_EXAMINER_CREATE_GROUP, '0') === '1',
        ]);
    }

    /**
     * Update settings (general, email, AI).
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_timezone' => 'nullable|string|max:100',
            'mail_mailer' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|string|max:10',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:512',
            'mail_encryption' => 'nullable|string|max:20',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'notify_result_ready' => 'nullable|boolean',
            'notify_result_email' => 'nullable|email|max:255',
            'gemini_api_key' => 'nullable|string|max:512',
            'clear_gemini_key' => 'nullable|boolean',
            'deepseek_api_key' => 'nullable|string|max:512',
            'clear_deepseek_key' => 'nullable|boolean',
            'cloudinary_cloud_name' => 'nullable|string|max:128',
            'cloudinary_api_key' => 'nullable|string|max:128',
            'cloudinary_api_secret' => 'nullable|string|max:512',
            'cloudinary_folder' => 'nullable|string|max:128',
            'lock_examiner_create_group' => 'nullable|boolean',
        ]);

        Setting::setValue(Setting::KEY_APP_NAME, $request->filled('app_name') ? trim($request->app_name) : null);
        Setting::setValue(Setting::KEY_APP_TIMEZONE, $request->filled('app_timezone') ? trim($request->app_timezone) : null);

        Setting::setValue(Setting::KEY_MAIL_MAILER, $request->filled('mail_mailer') ? trim($request->mail_mailer) : null);
        Setting::setValue(Setting::KEY_MAIL_HOST, $request->filled('mail_host') ? trim($request->mail_host) : null);
        Setting::setValue(Setting::KEY_MAIL_PORT, $request->filled('mail_port') ? trim($request->mail_port) : null);
        Setting::setValue(Setting::KEY_MAIL_USERNAME, $request->filled('mail_username') ? trim($request->mail_username) : null);
        if ($request->filled('mail_password')) {
            Setting::setValue(Setting::KEY_MAIL_PASSWORD, trim($request->mail_password));
        }
        Setting::setValue(Setting::KEY_MAIL_ENCRYPTION, $request->filled('mail_encryption') ? trim($request->mail_encryption) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_ADDRESS, $request->filled('mail_from_address') ? trim($request->mail_from_address) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_NAME, $request->filled('mail_from_name') ? trim($request->mail_from_name) : null);
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_READY, $request->boolean('notify_result_ready') ? '1' : '0');
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_EMAIL, $request->filled('notify_result_email') ? trim($request->notify_result_email) : null);

        if ($request->boolean('clear_gemini_key')) {
            Setting::setValue(Setting::KEY_GEMINI_API, null);
        } elseif ($request->filled('gemini_api_key')) {
            Setting::setValue(Setting::KEY_GEMINI_API, trim($request->gemini_api_key));
        }
        if ($request->boolean('clear_deepseek_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, null);
        } elseif ($request->filled('deepseek_api_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, trim($request->deepseek_api_key));
        }

        Setting::setValue(Setting::KEY_CLOUDINARY_CLOUD_NAME, $request->filled('cloudinary_cloud_name') ? trim($request->cloudinary_cloud_name) : null);
        if ($request->filled('cloudinary_api_key')) {
            Setting::setValue(Setting::KEY_CLOUDINARY_API_KEY, trim($request->cloudinary_api_key));
        }
        // Save API secret when provided (password field is always sent empty by browser when blank)
        $apiSecret = $request->input('cloudinary_api_secret');
        if (is_string($apiSecret) && trim($apiSecret) !== '') {
            Setting::setValue(Setting::KEY_CLOUDINARY_API_SECRET, trim($apiSecret));
        }
        Setting::setValue(Setting::KEY_CLOUDINARY_FOLDER, $request->filled('cloudinary_folder') ? trim($request->cloudinary_folder) : 'quizsnap');
        Setting::setValue(Setting::KEY_LOCK_EXAMINER_CREATE_GROUP, $request->boolean('lock_examiner_create_group') ? '1' : '0');

        // Ensure cache is cleared so Test Cloudinary / uploads use fresh DB values
        if ($request->hasAny(['cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret', 'cloudinary_folder'])) {
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_CLOUD_NAME);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_API_KEY);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_API_SECRET);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_FOLDER);
        }

        $tab = $request->input('settings_tab', 'general');
        $validTabs = ['general', 'email', 'ai', 'cloudinary'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'general';
        }
        return redirect()->route('dashboard.settings.index')->with('success', 'Settings saved.')->withFragment($tab);
    }

    /**
     * Test AI connection (Gemini / DeepSeek). Returns JSON for API or Settings page.
     */
    public function aiTest(AiQuestionService $ai): JsonResponse
    {
        $result = $ai->testConnection();
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Test Cloudinary connection. Returns JSON for Settings page.
     */
    public function cloudinaryTest(): JsonResponse
    {
        $result = CloudinaryService::testConnection();
        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
