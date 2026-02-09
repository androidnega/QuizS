<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Student;
use App\Services\ArkeselService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    private const OTP_CACHE_PREFIX = 'student_otp:';
    private const OTP_TTL_SECONDS = 86400; // 24 hours

    /**
     * Student account login form (index → phone → OTP flow).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (session('student_id')) {
            return redirect()->route('student.dashboard.index');
        }
        return view('student.account-login');
    }

    /**
     * Step 1: Verify index number. Index must exist in at least one class group.
     * Returns: need_phone (and student), or sends OTP and returns need_otp.
     */
    public function verifyIndex(Request $request): JsonResponse
    {
        $request->validate(['index_number' => 'required|string|max:100']);
        $indexNumber = strtoupper(trim($request->index_number));

        $exists = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexNumber])->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found. You must be added to a class group first.',
            ], 422);
        }

        $student = Student::firstOrCreate(
            ['index_number' => $indexNumber],
            ['index_number' => $indexNumber]
        );

        if (!$student->hasPhone()) {
            return response()->json([
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'message' => 'Enter your active phone number to receive a one-time code.',
            ]);
        }

        // Has phone: generate OTP and send
        $code = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $indexNumber, [
            'code' => $code,
            'phone' => $student->phone_contact,
        ], self::OTP_TTL_SECONDS);

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 24 hours.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send OTP. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your registered number.',
        ]);
    }

    /**
     * Step 2: Send OTP to the given phone (first-time or new phone). Ties phone to account after OTP verify.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
        ]);
        $indexNumber = strtoupper(trim($request->index_number));
        $phone = preg_replace('/\D/', '', trim($request->phone));
        if (strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 233XXXXXXXXX).',
            ], 422);
        }

        $student = Student::where('index_number', $indexNumber)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        $code = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $indexNumber, [
            'code' => $code,
            'phone' => $phone,
        ], self::OTP_TTL_SECONDS);

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 24 hours.';
        $result = ArkeselService::sendSms($phone, $message);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send code. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your number.',
        ]);
    }

    /**
     * Step 3: Verify OTP and create session. Optionally accept student_name to tie to account.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'code' => 'required|string|size:6',
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = strtoupper(trim($request->index_number));
        $code = trim($request->code);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;

        $cached = Cache::get(self::OTP_CACHE_PREFIX . $indexNumber);
        if (!$cached || ($cached['code'] ?? '') !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code. Please request a new one.',
            ], 422);
        }

        $student = Student::where('index_number', $indexNumber)->first();
        if (!$student) {
            Cache::forget(self::OTP_CACHE_PREFIX . $indexNumber);
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }

        // Tie phone to account if this was first-time (phone from cache)
        $phone = $cached['phone'] ?? null;
        if ($phone && !$student->phone_contact) {
            $student->phone_contact = $phone;
        }
        if ($name !== null && $name !== '') {
            $student->student_name = $name;
        }
        $student->save();

        Cache::forget(self::OTP_CACHE_PREFIX . $indexNumber);

        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('student.dashboard.index'),
        ]);
    }

    /**
     * Log out student (clear session and redirect to login).
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['student_id', 'student_index']);
        return redirect()->route('student.account.login.form')->with('success', 'You have been logged out.');
    }
}
