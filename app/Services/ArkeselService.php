<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Arkesel API integration for SMS and OTP.
 * Docs: https://developers.arkesel.com/
 * API key from https://sms.arkesel.com/dashboard (SMS API section).
 */
class ArkeselService
{
    private const BASE_URL = 'https://sms.arkesel.com';

    public static function hasApiKey(): bool
    {
        $key = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY, '');
        return is_string($key) && trim($key) !== '';
    }

    /**
     * Send SMS via Arkesel API v2.
     * Recipient: international format e.g. 233544919953 (Ghana).
     */
    public static function sendSms(string $recipient, string $message, ?string $senderId = null): array
    {
        $apiKey = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY, '');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Arkesel API key is not configured.'];
        }

        $sender = $senderId ?? Setting::getValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, 'QuizSnap');
        $sender = substr(trim($sender), 0, 11);

        $recipient = preg_replace('/\D/', '', $recipient);
        if ($recipient === '') {
            return ['success' => false, 'message' => 'Invalid recipient number.'];
        }
        if (strlen($recipient) < 10) {
            return ['success' => false, 'message' => 'Recipient number too short (use international format, e.g. 233XXXXXXXXX).'];
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::BASE_URL . '/api/v2/sms/send', [
            'sender' => $sender,
            'recipients' => [$recipient],
            'message' => $message,
        ]);

        $body = $response->json();
        $status = $response->status();

        if ($status === 200 && isset($body['status']) && $body['status'] === 'success') {
            return ['success' => true, 'message' => 'SMS sent successfully.'];
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if (is_array($errorMessage)) {
            $errorMessage = json_encode($errorMessage);
        }
        if ($status === 401) {
            $errorMessage = 'Authentication failed. Check your API key.';
        }
        if ($status === 402) {
            $errorMessage = 'Insufficient balance. Top up at Arkesel dashboard.';
        }
        if ($status === 422) {
            $errorMessage = 'Validation error: ' . (is_string($errorMessage) ? $errorMessage : json_encode($errorMessage));
        }

        Log::warning('Arkesel SMS send failed', ['status' => $status, 'body' => $body]);

        return ['success' => false, 'message' => $errorMessage];
    }

    /**
     * Send a test OTP (6-digit code) via SMS. Used for testing OTP delivery from Settings.
     */
    public static function sendTestOtp(string $recipient): array
    {
        $code = (string) random_int(100000, 999999);
        $message = 'Your QuizSnap OTP test code is: ' . $code . '. Do not share.';
        $result = self::sendSms($recipient, $message);
        if ($result['success']) {
            $result['message'] = 'Test OTP sent successfully to ' . $recipient . '.';
        }
        return $result;
    }
}
