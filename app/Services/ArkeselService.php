<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
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
     * Check Arkesel SMS and main balance. Returns ['success' => bool, 'message' => string, 'sms_balance' => string|null, 'main_balance' => string|null].
     */
    public static function checkBalance(): array
    {
        $apiKey = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY, '');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Arkesel API key is not configured.'];
        }
        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(10)
                ->get(self::BASE_URL . '/api/v2/clients/balance-details');
        } catch (ConnectionException $e) {
            Log::warning('Arkesel balance check failed', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Arkesel.'];
        }
        $body = $response->json();
        $status = $response->status();
        if ($status === 200 && isset($body['status']) && $body['status'] === 'success' && isset($body['data'])) {
            $data = $body['data'];
            return [
                'success' => true,
                'message' => 'Balance retrieved.',
                'sms_balance' => $data['sms_balance'] ?? null,
                'main_balance' => $data['main_balance'] ?? null,
            ];
        }
        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if ($status === 401) {
            $errorMessage = 'Invalid API key.';
        }
        return ['success' => false, 'message' => is_string($errorMessage) ? $errorMessage : json_encode($errorMessage)];
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

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(10)
                ->post(self::BASE_URL . '/api/v2/sms/send', [
                    'sender' => $sender,
                    'recipients' => [$recipient],
                    'message' => $message,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Arkesel SMS connection failed', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Arkesel. Please try again.'];
        }

        $body = $response->json();
        $status = $response->status();

        // Log response for debugging (no API key)
        Log::info('Arkesel SMS response', ['status' => $status, 'body' => $body]);

        if ($status === 200 && isset($body['status']) && $body['status'] === 'success') {
            // Check if our recipient was rejected as invalid in the response data
            $data = $body['data'] ?? [];
            foreach (is_array($data) ? $data : [] as $item) {
                if (isset($item['invalid numbers']) && is_array($item['invalid numbers']) && in_array($recipient, $item['invalid numbers'], true)) {
                    Log::warning('Arkesel SMS recipient invalid', ['recipient' => $recipient]);
                    return ['success' => false, 'message' => 'Phone number not valid for SMS delivery. Use international format (e.g. 233544919953 for Ghana).'];
                }
            }
            return ['success' => true, 'message' => 'SMS sent successfully.'];
        }

        $errorMessage = $body['message'] ?? $body['error'] ?? 'Unknown error';
        if (is_array($errorMessage)) {
            $errorMessage = json_encode($errorMessage);
        }
        if ($status === 401) {
            $errorMessage = 'Authentication failed. Check your API key at Arkesel Dashboard → SMS API.';
        }
        if ($status === 402) {
            $errorMessage = 'Insufficient balance. Top up at https://sms.arkesel.com (Recharge / Purchase SMS Plan).';
        }
        if ($status === 403) {
            $errorMessage = 'Arkesel gateway inactive. Activate your account or contact support@arkesel.com.';
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
