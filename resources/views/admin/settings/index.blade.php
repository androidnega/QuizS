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
                        <button type="button" class="settings-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-tab="otp" id="tab-btn-otp">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            OTP (SMS)
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
                    @if(session('admin_role') === 'super_admin')
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Examiner permissions</p>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="lock_examiner_create_group" value="1" {{ old('lock_examiner_create_group', $lock_examiner_create_group ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-sm text-gray-700">Lock examiners from creating new class groups</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">When on, only Super Admin can create class groups. Examiners can still view and edit existing groups.</p>

                        <div class="mt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="allow_examiner_create_course" value="1" {{ old('allow_examiner_create_course', $allow_examiner_create_course ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm text-gray-700">Allow examiners to create courses</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">When enabled, examiners can create and manage courses. By default, only Super Admin can create courses.</p>
                        </div>

                        <div class="mt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="disable_ip_device_restrictions" value="1" {{ old('disable_ip_device_restrictions', $disable_ip_device_restrictions ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm text-gray-700">Allow shared networks/PCs for quiz access (disable IP/computer uniqueness restriction)</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">When enabled, students can take the same quiz from the same network or reused computers. This also disables session IP mismatch blocking during the quiz.</p>
                        </div>
                    </div>
                    @endif
                </div>
                </div>

                <!-- Tab: Email -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="email" id="tab-content-email">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Email</h2>
                <p class="text-sm text-gray-600 mb-4">Outgoing mail configuration. Stored in database (password encrypted). Used for password reset and notifications.</p>
                <div class="rounded-lg border border-primary-200 bg-primary-50 p-3 mb-4 text-sm text-primary-800">
                    <p class="font-medium">Secure SSL/TLS (recommended):</p>
                    <p class="mt-1">Host: mail.ausweblabs.com — SMTP Port: 465 (SSL). Username: reset@ausweblabs.com. Use the email account’s password. IMAP/POP3/SMTP require authentication.</p>
                </div>
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
                            <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $mail_host ?? 'mail.ausweblabs.com') }}" class="input w-full" placeholder="mail.ausweblabs.com">
                        </div>
                        <div>
                            <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                            <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port', $mail_port ?? '465') }}" class="input w-full" placeholder="465">
                        </div>
                    </div>
                    <div>
                        <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $mail_username ?? 'reset@ausweblabs.com') }}" class="input w-full" placeholder="reset@ausweblabs.com" autocomplete="off">
                    </div>
                    <div>
                        <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="mail_password" id="mail_password" class="input w-full" autocomplete="new-password" placeholder="Use the email account’s password (stored encrypted)">
                    </div>
                    <div>
                        <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                        <select name="mail_encryption" id="mail_encryption" class="input w-full">
                            <option value="tls" {{ ($mail_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($mail_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ ($mail_encryption ?? '') === '' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1">From address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $mail_from_address ?? 'reset@ausweblabs.com') }}" class="input w-full" placeholder="reset@ausweblabs.com">
                        </div>
                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1">From name</label>
                            <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $mail_from_name ?? 'QuizSnap') }}" class="input w-full" placeholder="QuizSnap">
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
                <p class="text-sm text-gray-600 mb-4">All API keys below are stored in the database and used for question generation: Gemini (primary), then DeepSeek (fallback) if Gemini is missing or fails. Cloudinary (Cloudinary tab) is also stored in the database and used for proctoring and institution logo.</p>
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
                    @unless(app()->environment('production'))
                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Uses the saved API key (Gemini first, then DeepSeek). Save settings first if you just changed a key.</p>
                        <button type="button" id="ai-test-btn" class="btn btn-secondary">
                            Test AI connection
                        </button>
                        <div id="ai-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    @endunless
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
                    @unless(app()->environment('production'))
                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first, then test. Uploads a tiny test image to verify credentials.</p>
                        <button type="button" id="cloudinary-test-btn" class="btn btn-secondary">
                            Test Cloudinary
                        </button>
                        <div id="cloudinary-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    @endunless
                </div>
                </div>

                <!-- Tab: OTP (Arkesel) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="otp" id="tab-content-otp">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">OTP Providers (SMS)</h2>
                <p class="text-sm text-gray-600 mb-4">Configure SMS OTP delivery via <a href="https://arkesel.com" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Arkesel</a>. API keys are stored encrypted. Use the test below to verify delivery.</p>
                <div class="space-y-4">
                    <div>
                        <label for="otp_arkesel_api_key" class="block text-sm font-medium text-gray-700 mb-2">Arkesel API Key</label>
                        @if($otp_arkesel_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-2">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded">{{ $otp_arkesel_key_masked ?? '' }}</code></p>
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" class="input w-full" placeholder="Enter new key to replace, or leave blank to keep">
                            <label class="flex items-center gap-2 cursor-pointer mt-2">
                                <input type="checkbox" name="clear_otp_arkesel_key" value="1" class="w-4 h-4 text-danger-600 border-gray-300 rounded focus:ring-danger-500">
                                <span class="text-sm text-gray-700">Remove Arkesel API key</span>
                            </label>
                        @else
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" class="input w-full" placeholder="Your Arkesel API key">
                            <p class="text-xs text-gray-500 mt-1">Get your key from <a href="https://sms.arkesel.com/dashboard" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Arkesel Dashboard</a> → SMS API. Stored encrypted.</p>
                        @endif
                    </div>
                    <div>
                        <label for="otp_arkesel_sender_id" class="block text-sm font-medium text-gray-700 mb-1">Sender ID (optional)</label>
                        <input type="text" name="otp_arkesel_sender_id" id="otp_arkesel_sender_id" value="{{ old('otp_arkesel_sender_id', $otp_arkesel_sender_id ?? 'QuizSnap') }}" class="input w-full max-w-xs" placeholder="QuizSnap" maxlength="11">
                        <p class="text-xs text-gray-500 mt-1">Max 11 characters. Shown as SMS sender (e.g. QuizSnap).</p>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Account balance</p>
                        <p class="text-xs text-gray-500 mb-2">Verify your Arkesel account has SMS credits (required for delivery).</p>
                        <button type="button" id="otp-balance-btn" class="btn btn-secondary mb-2">Check balance</button>
                        <div id="otp-balance-result" class="mt-2 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Test OTP delivery</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first if you changed the API key. Use international format (e.g. 233544919953 for Ghana). If you don’t receive the SMS, check balance above and your Arkesel dashboard for delivery status.</p>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="otp-test-phone" class="block text-xs text-gray-600 mb-1">Phone number</label>
                                <input type="text" id="otp-test-phone" class="input w-48" placeholder="233544919953" autocomplete="off">
                            </div>
                            <button type="button" id="otp-test-btn" class="btn btn-secondary">Send test OTP</button>
                        </div>
                        <div id="otp-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
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
    const validTabs = ['general', 'email', 'ai', 'cloudinary', 'otp'];

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

@unless(app()->environment('production'))
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
var aiTestBtn = document.getElementById('ai-test-btn');
if (aiTestBtn) {
    aiTestBtn.addEventListener('click', function() {
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
}
@endunless

// OTP Balance check & Test (available in all environments)
document.addEventListener('DOMContentLoaded', function() {
    var otpBalanceBtn = document.getElementById('otp-balance-btn');
    var otpBalanceResult = document.getElementById('otp-balance-result');
    if (otpBalanceBtn && otpBalanceResult) {
        otpBalanceBtn.addEventListener('click', function() {
            otpBalanceResult.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            otpBalanceResult.textContent = 'Checking…';
            otpBalanceBtn.disabled = true;
            fetch('{{ route('dashboard.settings.otp-balance') }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
                .then(function(res) {
                    var d = res.data;
                    otpBalanceResult.classList.remove('hidden');
                    if (d.success) {
                        otpBalanceResult.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                        otpBalanceResult.textContent = 'SMS balance: ' + (d.sms_balance != null ? d.sms_balance : '—') + ' | Main balance: ' + (d.main_balance != null ? d.main_balance : '—');
                    } else {
                        otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                        otpBalanceResult.textContent = d.message || 'Could not check balance.';
                    }
                })
                .catch(function(err) {
                    otpBalanceResult.classList.remove('hidden');
                    otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    otpBalanceResult.textContent = 'Request failed: ' + (err.message || 'Network error');
                })
                .finally(function() { otpBalanceBtn.disabled = false; });
        });
    }

    var otpTestBtn = document.getElementById('otp-test-btn');
    if (otpTestBtn) {
        otpTestBtn.addEventListener('click', function() {
            var phoneInput = document.getElementById('otp-test-phone');
            var resultEl = document.getElementById('otp-test-result');
            var phone = phoneInput && phoneInput.value ? phoneInput.value.trim() : '';
            if (!phone) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Enter a phone number first.';
                return;
            }
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Sending test OTP…';
            otpTestBtn.disabled = true;
            var formData = new FormData();
            formData.append('phone', phone);
            formData.append('_token', document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);
            fetch('{{ route('dashboard.settings.otp-test') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data;
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Test OTP sent.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = d.message || 'Failed to send test OTP.';
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { otpTestBtn.disabled = false; });
        });
    }
});
</script>
@endpush
@endsection
