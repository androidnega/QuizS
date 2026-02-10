<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StudentLoginController extends Controller
{
    /**
     * Show index number entry. Quiz is fixed from the link (stored in session after rules acceptance).
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in. Please logout first to login with a different account.');
        }
        
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as staff. Please logout first to take a quiz.');
        }
        
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            return redirect()->route('student.landing')
                ->with('error', 'Please start from the quiz link. Enter the link on the start page first.');
        }
        $quiz = Quiz::where('id', $quizId)->where('is_active', true)->first();
        if (!$quiz || !$quiz->isActive()) {
            return redirect()->route('student.landing')->with('error', 'This quiz is no longer active or the link has expired.');
        }
        return view('student.login', ['quiz' => $quiz]);
    }

    /**
     * Verify index against the quiz's class group student list. On success store quiz_id + index_number, redirect to proctoring.
     */
    public function verifyIndex(Request $request): JsonResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to take a quiz.',
            ], 422);
        }
        
        $request->validate(['index_number' => 'required|string']);
        $indexNumber = strtoupper(trim($request->index_number));
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start from the quiz link again.',
            ], 422);
        }

        $quiz = Quiz::with('classGroup')->find($quizId);
        if (!$quiz || !$quiz->isActive() || !$quiz->class_group_id) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available.',
            ], 422);
        }

        $exists = ClassGroupStudent::where('class_group_id', $quiz->class_group_id)
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])
            ->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found for this class group.',
            ], 422);
        }

        if ($this->isIpDeviceRestrictionEnabled()) {
            $ip = $request->ip();
            if (QuizSession::where('quiz_id', $quiz->id)->where('ip_address', $ip)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This IP address has already been used for this quiz. Access denied.',
                ], 422);
            }
        }

        session([
            'quiz_id' => $quiz->id,
            'index_number' => $indexNumber,
        ]);
        session()->forget('quiz_id_for_login');

        $student = Student::firstOrCreate(
            ['index_number' => $indexNumber],
            ['index_number' => $indexNumber]
        );

        if (!$student->hasPhone()) {
            return response()->json([
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'message' => 'Enter an active phone number to receive an SMS. We\'ll save it to your index for future logins. The code we send will also be your login for the next 24 hours—keep it.',
            ]);
        }

        // Check if there's an existing valid OTP (within 24 hours)
        $cached = Cache::get('student_otp:' . $indexNumber);
        if ($cached && isset($cached['code'])) {
            // OTP already exists and is valid, no need to send new SMS
            return response()->json([
                'success' => true,
                'step' => 'otp',
                'index_number' => $student->index_number,
                'message' => 'Use your existing code sent within the last 24 hours, or request a new one below.',
            ]);
        }

        // No valid OTP, generate and send new one
        $code = (string) random_int(100000, 999999);
        Cache::put('student_otp:' . $indexNumber, ['code' => $code, 'phone' => $student->phone_contact], 86400);
        $message = 'Your QuizSnap code is: ' . $code . '. Use it to continue the quiz and as login for 24 hours. Do not share.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your registered number. This code is valid for 24 hours.',
        ]);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
