<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key. Cached for the request. Decrypts if key is sensitive.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $cacheKey = 'setting:' . $key;
        $value = Cache::remember($cacheKey, 3600, function () use ($key) {
            $row = static::where('key', $key)->first();
            return $row?->value;
        });
        if ($value === null) {
            return $default;
        }
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Set a setting value by key. Encrypts if key is sensitive.
     */
    public static function setValue(string $key, ?string $value): void
    {
        $stored = $value;
        if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED_KEYS, true)) {
            $stored = Crypt::encryptString($value);
        }
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored]
        );
        Cache::forget('setting:' . $key);
    }

    public const KEY_OPENAI_API = 'openai_api_key';
    public const KEY_GEMINI_API = 'gemini_api_key';
    public const KEY_DEEPSEEK_API = 'deepseek_api_key';

    /** General */
    public const KEY_APP_NAME = 'app_name';
    public const KEY_APP_TIMEZONE = 'app_timezone';
    public const KEY_INSTITUTION_NAME = 'institution_name';
    public const KEY_INSTITUTION_LOGO = 'institution_logo';

    /** Cloudinary (proctoring / result photos) */
    public const KEY_CLOUDINARY_CLOUD_NAME = 'cloudinary_cloud_name';
    public const KEY_CLOUDINARY_API_KEY = 'cloudinary_api_key';
    public const KEY_CLOUDINARY_API_SECRET = 'cloudinary_api_secret';
    public const KEY_CLOUDINARY_FOLDER = 'cloudinary_folder';

    /** Mail */
    public const KEY_MAIL_MAILER = 'mail_mailer';
    public const KEY_MAIL_HOST = 'mail_host';
    public const KEY_MAIL_PORT = 'mail_port';
    public const KEY_MAIL_USERNAME = 'mail_username';
    public const KEY_MAIL_PASSWORD = 'mail_password';
    public const KEY_MAIL_ENCRYPTION = 'mail_encryption';
    public const KEY_MAIL_FROM_ADDRESS = 'mail_from_address';
    public const KEY_MAIL_FROM_NAME = 'mail_from_name';

    /** Notifications: send email when a student submits a quiz (result ready). */
    public const KEY_NOTIFY_RESULT_READY = 'notify_result_ready';
    public const KEY_NOTIFY_RESULT_EMAIL = 'notify_result_email';

    /** Admin: lock examiners from creating new class groups (1 = locked). */
    public const KEY_LOCK_EXAMINER_CREATE_GROUP = 'lock_examiner_create_group';

    /** Keys whose values are stored encrypted (API keys, secrets, mail password). */
    private const ENCRYPTED_KEYS = [
        self::KEY_GEMINI_API,
        self::KEY_DEEPSEEK_API,
        self::KEY_OPENAI_API,
        self::KEY_CLOUDINARY_API_KEY,
        self::KEY_CLOUDINARY_API_SECRET,
        self::KEY_MAIL_PASSWORD,
    ];
}
