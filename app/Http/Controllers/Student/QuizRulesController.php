<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class QuizRulesController extends Controller
{
    /**
     * Show quiz rules & warning screen. Optional token (link_token) for context; generic rules when none.
     * When quiz link is invalid or expired, show link-expired view.
     * When quiz has a future starts_at, redirect to countdown page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = null;
        if ($token) {
            $quiz = Quiz::with('course')->where('link_token', $token)->first();
            if (!$quiz || !$quiz->is_published || !$quiz->hasEnoughApprovedQuestions()) {
                return view('student.link-expired');
            }
            if ($quiz->ends_at && $quiz->ends_at->isPast()) {
                return view('student.link-expired');
            }
            if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $token]);
            }
        }
        return view('student.quiz-rules', compact('quiz'));
    }

    /**
     * Show "Quiz will start at X" countdown page when quiz has a future starts_at.
     * When countdown reaches zero, student can proceed to rules.
     */
    public function quizWillStart(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = Quiz::with('course')->where('link_token', $token)->first();
        if (!$quiz || !$quiz->is_published || !$quiz->hasEnoughApprovedQuestions()) {
            return view('student.link-expired');
        }
        if ($quiz->ends_at && $quiz->ends_at->isPast()) {
            return view('student.link-expired');
        }
        if (!$quiz->starts_at || $quiz->starts_at->isPast()) {
            return redirect()->route('student.rules.show.quiz', ['token' => $token]);
        }
        return view('student.quiz-will-start', compact('quiz'));
    }

    /**
     * Store acceptance (dos & don'ts accepted). Store quiz_id in session so login validates index against this quiz's class group.
     */
    public function accept(Request $request): JsonResponse
    {
        $quizId = $request->input('quiz_id');
        $sessionData = ['rules_accepted' => true];
        if ($quizId) {
            $request->validate(['quiz_id' => 'exists:quizzes,id']);
            $quiz = Quiz::find($quizId);
            if ($quiz && $quiz->isActive()) {
                $indexNumber = $request->input('index_number') ?? session('student_index') ?? 'pending';
                QuizAcceptance::create([
                    'quiz_id' => $quiz->id,
                    'index_number' => $indexNumber,
                    'ip_address' => $request->ip(),
                    'accepted_at' => now(),
                ]);
                $sessionData['quiz_id_for_login'] = $quiz->id;
            }
        }

        session($sessionData);
        session()->forget('eligible_courses');

        return response()->json([
            'success' => true,
            'redirect' => route('student.login.form'),
        ]);
    }
}
