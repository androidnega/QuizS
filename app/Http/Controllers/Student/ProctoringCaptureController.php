<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\CloudinaryService;
use App\Services\QuestionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProctoringCaptureController extends Controller
{
    public function __construct(
        private QuestionAssignmentService $assignmentService
    ) {}

    /**
     * Show face capture screen (ProctoringCapture). Quiz and index from session.
     */
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse|JsonResponse
    {
        $quizId = session('quiz_id');
        $indexNumber = session('index_number');
        if (!$quizId || !$indexNumber) {
            return redirect()->route('student.login.form')->with('error', 'Please complete index and course selection first.');
        }
        $quiz = Quiz::find($quizId);
        if (!$quiz || !$quiz->isActive()) {
            return redirect()->route('student.landing')->with('error', 'Quiz is not active.');
        }
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $indexNumber));
        
        if ($this->isIpDeviceRestrictionEnabled()) {
            // Check if IP was used by a different student for this quiz
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw('UPPER(TRIM(student_index)) != ?', [$studentIndex])
                ->exists();

            if ($ipUsedByOther) {
                return redirect()->route('student.landing')->with('error', 'This IP has already been used for this quiz by another student.');
            }
        }
        return view('student.proctoring-capture', [
            'quiz' => $quiz,
            'indexNumber' => $indexNumber,
        ]);
    }

    /**
     * Store face image, bind IP, create session, assign questions.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'index_number' => 'required|string',
            'face_image' => 'required|string', // base64 data URL
        ]);
        $quiz = Quiz::with('questions')->findOrFail($request->quiz_id);
        if (!$quiz->isActive()) {
            return response()->json(['success' => false, 'message' => 'Quiz is not active.'], 403);
        }
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $request->index_number));
        
        if ($this->isIpDeviceRestrictionEnabled()) {
            // Check if IP was used by a different student for this quiz
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw('UPPER(TRIM(student_index)) != ?', [$studentIndex])
                ->exists();

            if ($ipUsedByOther) {
                return response()->json(['success' => false, 'message' => 'IP already used for this quiz by another student.'], 403);
            }
        }

        $studentIndex = strtoupper(trim((string) $request->index_number));
        $imagePath = null;
        $preFaceImageHash = null;
        $data = $request->face_image;
        if (Str::startsWith($data, 'data:image')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $imageBytes = base64_decode($base64, true);
            if ($imageBytes !== false) {
                $preFaceImageHash = hash('sha256', $imageBytes);
            }
            if (CloudinaryService::isConfigured()) {
                $imagePath = CloudinaryService::uploadFromDataUrl($data, 'pre_q' . $quiz->id . '_' . $studentIndex);
            }
            if ($imagePath === null && $imageBytes !== false) {
                $imagePath = 'proctoring/pre_' . $quiz->id . '_' . $studentIndex . '_' . time() . '.jpg';
                Storage::disk('public')->put($imagePath, $imageBytes);
            }
        }

        $assignment = $this->assignmentService->assignQuestions($quiz);
        $assignedIds = $assignment['question_ids'] ?? [];
        if (count($assignedIds) < $quiz->getQuestionsPerStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough approved questions for this quiz. Please approve more questions in the admin panel.',
            ], 403);
        }
        $correctAnswersSnapshot = $assignment['correct_answers'] ?? [];
        $shuffledOptions = $assignment['shuffled_options'] ?? [];
        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'student_index' => $studentIndex,
            'ip_address' => $ip,
            'start_time' => null, // timer starts when student clicks Start Quiz on readiness screen
            'pre_face_image' => $imagePath,
            'pre_face_image_hash' => $preFaceImageHash,
            'assigned_question_ids' => $assignedIds,
            'assigned_correct_answers' => $correctAnswersSnapshot,
            'shuffled_question_options' => $shuffledOptions,
            'session_token' => QuizSession::generateToken(),
        ]);

        // Store quiz session token in HttpOnly session only; no token in response or URL
        session(['quiz_session_token' => $session->session_token]);

        return response()->json([
            'success' => true,
            'redirect' => route('student.quiz.ready'),
        ]);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
