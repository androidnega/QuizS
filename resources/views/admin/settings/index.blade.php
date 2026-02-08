@extends('layouts.dashboard')

@section('title', 'Settings')
@section('dashboard_heading', 'Settings')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 font-medium">Settings</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Settings</h1>
            <p class="text-gray-600 mt-1">System configuration: general, email, AI, and Cloudinary</p>
        </div>

        <form action="{{ route('dashboard.settings.update') }}" method="post" class="space-y-8" id="settings-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="settings_tab" id="settings_tab" value="general">

            <!-- Tabs Navigation -->
            <div class="card overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button type="button" class="settings-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-tab="general" id="tab-btn-general">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            General
                        </button>
                        <button type="button" class="settings-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-tab="email" id="tab-btn-email">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email
                        </button>
                        <button type="button" class="settings-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-tab="ai" id="tab-btn-ai">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            AI
                        </button>
                        <button type="button" class="settings-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-tab="cloudinary" id="tab-btn-cloudinary">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Cloudinary
                        </button>
                    </nav>
                </div>

                <!-- Tab: General -->
                <div class="settings-tab-content p-6" data-tab-content="general" id="tab-content-general">
                <div class="space-y-4">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">Application name</label>
                        <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $app_name ?? '') }}" class="input w-full" placeholder="QuizSnap">
                        <p class="text-xs text-gray-500 mt-1">Used in page titles and emails. Leave blank to use default.</p>
                    </div>
                    <div>
                        <label for="app_timezone" class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                        <input type="text" name="app_timezone" id="app_timezone" value="{{ old('app_timezone', $app_timezone ?? 'UTC') }}" class="input w-full" placeholder="UTC">
                        <p class="text-xs text-gray-500 mt-1">e.g. UTC, Africa/Nairobi, America/New_York</p>
                    </div>
                    <div>
                        <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-1">Institution / School name</label>
                        <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $institution_name ?? '') }}" class="input w-full" placeholder="e.g. Takoradi Technical University">
                        <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports. Leave blank to omit.</p>
                    </div>
                    <div>
                        <label for="institution_logo" class="block text-sm font-medium text-gray-700 mb-1">Institution logo</label>
                        @if(!empty($institution_logo))
                            <p class="text-xs text-gray-500 mb-1">Current: <img src="{{ asset('storage/' . $institution_logo) }}" alt="Logo" class="inline-block h-8 max-w-[120px] object-contain"> — upload new to replace</p>
                        @endif
                        <input type="file" name="institution_logo" id="institution_logo" accept="image/*" class="input w-full text-sm">
                        <p class="text-xs text-gray-500 mt-1">Image for PDF header. Max 2MB.</p>
                    </div>
                </div>
                </div>

                <!-- Tab: Email -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="email" id="tab-content-email">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Email</h2>
                <p class="text-sm text-gray-600 mb-4">Outgoing mail configuration. Stored in database and used when the app sends email.</p>
                <div class="space-y-4">
                    <div>
                        <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-1">Mailer</label>
                        <select name="mail_mailer" id="mail_mailer" class="input w-full">
                            <option value="smtp" {{ ($mail_mailer ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ ($mail_mailer ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="log" {{ ($mail_mailer ?? '') === 'log' ? 'selected' : '' }}>Log (no send)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                            <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $mail_host ?? '') }}" class="input w-full" placeholder="smtp.mailtrap.io">
                        </div>
                        <div>
                            <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                            <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port', $mail_port ?? '587') }}" class="input w-full" placeholder="587">
                        </div>
                    </div>
                    <div>
                        <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $mail_username ?? '') }}" class="input w-full" autocomplete="off">
                    </div>
                    <div>
                        <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="mail_password" id="mail_password" class="input w-full" autocomplete="new-password" placeholder="Leave blank to keep current">
                    </div>
                    <div>
                        <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                        <select name="mail_encryption" id="mail_encryption" class="input w-full">
                            <option value="tls" {{ ($mail_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($mail_encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ ($mail_encryption ?? '') === '' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1">From address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $mail_from_address ?? '') }}" class="input w-full" placeholder="noreply@example.com">
                        </div>
                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1">From name</label>
                            <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $mail_from_name ?? '') }}" class="input w-full" placeholder="QuizSnap">
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 space-y-2">
                        <p class="text-sm font-medium text-gray-700">Result-ready notification</p>
                        <p class="text-xs text-gray-500 mb-2">Send an email when a student submits a quiz (result ready).</p>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="notify_result_ready" value="1" {{ old('notify_result_ready', $notify_result_ready ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-sm text-gray-700">Enable result-ready email</span>
                        </label>
                        <div class="mt-2">
                            <label for="notify_result_email" class="block text-sm font-medium text-gray-700 mb-1">Send to (email)</label>
                            <input type="email" name="notify_result_email" id="notify_result_email" value="{{ old('notify_result_email', $notify_result_email ?? '') }}" class="input w-full" placeholder="examiner@example.com">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to disable. One email per submission.</p>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Tab: AI -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="ai" id="tab-content-ai">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">AI Question Generation</h2>
                <p class="text-sm text-gray-600 mb-4">Gemini (primary) and DeepSeek (fallback) API keys.</p>
                <div class="space-y-6">
                    <div>
                        <label for="gemini_api_key" class="block text-sm font-medium text-gray-700 mb-2">Gemini API Key (primary)</label>
                        @if($gemini_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-2">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded">{{ $gemini_key_masked ?? '' }}</code></p>
                            <input type="password" name="gemini_api_key" id="gemini_api_key" autocomplete="off" class="input w-full" placeholder="Enter new key to replace, or leave blank to keep">
                            <label class="flex items-center gap-2 cursor-pointer mt-2">
                                <input type="checkbox" name="clear_gemini_key" value="1" class="w-4 h-4 text-danger-600 border-gray-300 rounded focus:ring-danger-500">
                                <span class="text-sm text-gray-700">Remove Gemini key</span>
                            </label>
                        @else
                            <input type="password" name="gemini_api_key" id="gemini_api_key" autocomplete="off" class="input w-full" placeholder="AIza...">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Google AI Studio</a> (Gemini API).</p>
                        @endif
                        @error('gemini_api_key')<p class="text-sm text-danger-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="deepseek_api_key" class="block text-sm font-medium text-gray-700 mb-2">DeepSeek API Key (fallback)</label>
                        @if($deepseek_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-2">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded">{{ $deepseek_key_masked ?? '' }}</code></p>
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" class="input w-full" placeholder="Enter new key to replace, or leave blank to keep">
                            <label class="flex items-center gap-2 cursor-pointer mt-2">
                                <input type="checkbox" name="clear_deepseek_key" value="1" class="w-4 h-4 text-danger-600 border-gray-300 rounded focus:ring-danger-500">
                                <span class="text-sm text-gray-700">Remove DeepSeek key</span>
                            </label>
                        @else
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" class="input w-full" placeholder="sk-...">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener" class="text-primary-600 hover:underline">DeepSeek Platform</a>. Used when Gemini is not set or fails.</p>
                        @endif
                        @error('deepseek_api_key')<p class="text-sm text-danger-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Uses the saved API key (Gemini first, then DeepSeek). Save settings first if you just changed a key.</p>
                        <button type="button" id="ai-test-btn" class="btn btn-secondary">
                            Test AI connection
                        </button>
                        <div id="ai-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                </div>
                </div>

                <!-- Tab: Cloudinary -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="cloudinary" id="tab-content-cloudinary">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Cloudinary (Proctoring &amp; result photos)</h2>
                <p class="text-sm text-gray-600 mb-4">Store student face photos (before and after quiz) on Cloudinary for lightweight, fast delivery. Leave blank to keep storing images locally.</p>
                <div class="space-y-4">
                    <div>
                        <label for="cloudinary_cloud_name" class="block text-sm font-medium text-gray-700 mb-1">Cloud name</label>
                        <input type="text" name="cloudinary_cloud_name" id="cloudinary_cloud_name" value="{{ old('cloudinary_cloud_name', $cloudinary_cloud_name ?? '') }}" class="input w-full" placeholder="your-cloud-name" autocomplete="off">
                        <p class="text-xs text-gray-500 mt-1">From your <a href="https://console.cloudinary.com/" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Cloudinary dashboard</a>.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cloudinary_api_key" class="block text-sm font-medium text-gray-700 mb-1">API key</label>
                            @if($cloudinary_key_set ?? false)
                                <p class="text-sm text-gray-600 mb-2">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded">{{ $cloudinary_key_masked ?? '' }}</code></p>
                                <input type="text" name="cloudinary_api_key" id="cloudinary_api_key" value="{{ old('cloudinary_api_key') }}" class="input w-full" placeholder="Enter new key to replace, or leave blank to keep" autocomplete="off">
                            @else
                                <input type="text" name="cloudinary_api_key" id="cloudinary_api_key" value="{{ old('cloudinary_api_key') }}" class="input w-full" placeholder="123456789012345" autocomplete="off">
                            @endif
                        </div>
                        <div>
                            <label for="cloudinary_api_secret" class="block text-sm font-medium text-gray-700 mb-1">API secret</label>
                            @if($cloudinary_secret_set ?? false)
                                <p class="text-sm text-gray-600 mb-2">Current secret: <code class="px-2 py-0.5 bg-gray-100 rounded">••••••••</code> (saved)</p>
                            @endif
                            <input type="password" name="cloudinary_api_secret" id="cloudinary_api_secret" value="" class="input w-full" placeholder="{{ ($cloudinary_secret_set ?? false) ? 'Enter new secret to replace, or leave blank to keep' : 'Enter your Cloudinary API secret' }}" autocomplete="new-password">
                            <p class="text-xs text-gray-500 mt-1">Required for uploads. Stored in database and used when calling Cloudinary.</p>
                        </div>
                    </div>
                    <div>
                        <label for="cloudinary_folder" class="block text-sm font-medium text-gray-700 mb-1">Folder (optional)</label>
                        <input type="text" name="cloudinary_folder" id="cloudinary_folder" value="{{ old('cloudinary_folder', $cloudinary_folder ?? '') }}" class="input w-full" placeholder="quizsnap">
                        <p class="text-xs text-gray-500 mt-1">Subfolder for uploads. Default: quizsnap. Images are optimized (quality_auto, fetch_format auto) for lightweight storage.</p>
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first, then test. Uploads a tiny test image to verify credentials.</p>
                        <button type="button" id="cloudinary-test-btn" class="btn btn-secondary">
                            Test Cloudinary
                        </button>
                        <div id="cloudinary-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save all settings
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Tab switching + persist tab in URL hash so refresh keeps user on same tab
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.settings-tab-btn');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    const validTabs = ['general', 'email', 'ai', 'cloudinary'];

    function switchToTab(targetTab) {
        if (!validTabs.includes(targetTab)) targetTab = 'general';
        location.hash = targetTab;
        tabBtns.forEach(function(b) {
            if (b.getAttribute('data-tab') === targetTab) {
                b.classList.add('border-primary-500', 'text-primary-600');
                b.classList.remove('border-transparent', 'text-gray-500');
            } else {
                b.classList.remove('border-primary-500', 'text-primary-600');
                b.classList.add('border-transparent', 'text-gray-500');
            }
        });
        tabContents.forEach(function(content) {
            content.classList.toggle('hidden', content.getAttribute('data-tab-content') !== targetTab);
        });
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchToTab(this.getAttribute('data-tab'));
        });
    });

    var hash = (location.hash || '').replace(/^#/, '');
    if (validTabs.includes(hash)) {
        switchToTab(hash);
    } else {
        switchToTab('general');
    }

    var form = document.getElementById('settings-form');
    if (form) {
        form.addEventListener('submit', function() {
            var tabInput = document.getElementById('settings_tab');
            if (tabInput) tabInput.value = (location.hash || '#general').replace(/^#/, '') || 'general';
        });
    }
});

// Cloudinary Test
document.addEventListener('DOMContentLoaded', function() {
    var cloudinaryBtn = document.getElementById('cloudinary-test-btn');
    if (cloudinaryBtn) {
        cloudinaryBtn.addEventListener('click', function() {
            var btn = this;
            var resultEl = document.getElementById('cloudinary-test-result');
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Testing…';
            btn.disabled = true;
            fetch('{{ route('dashboard.settings.cloudinary-test') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data;
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Cloudinary connection OK.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = (d.message || 'Cloudinary test failed.') + (d.detail ? ' ' + d.detail : '');
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { btn.disabled = false; });
        });
    }
});

// AI Test
document.getElementById('ai-test-btn').addEventListener('click', function() {
    var btn = this;
    var resultEl = document.getElementById('ai-test-result');
    resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
    resultEl.textContent = 'Testing…';
    btn.disabled = true;
    fetch('{{ route('dashboard.settings.ai-test') }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
        var d = res.data;
        resultEl.classList.remove('hidden');
        if (d.success) {
            resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
            resultEl.textContent = d.message + ' (Provider: ' + (d.provider || '') + (d.reply ? ', reply: ' + d.reply : '') + ')';
        } else {
            resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = d.message + (d.detail ? ' ' + d.detail : '');
        }
    })
    .catch(function(err) {
        resultEl.classList.remove('hidden');
        resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
        resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
    })
    .finally(function() {
        btn.disabled = false;
    });
});
</script>
@endpush
@endsection
