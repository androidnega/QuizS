<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PostQuizCaptureController extends Controller
{
    /**
     * Show final photo capture screen (same UI/layout as first proctoring capture).
     * Student must have an active quiz session; after capturing, they submit and are redirected to result.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.landing')->with('error', 'No active quiz session.');
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->first();
        if (!$session) {
            return redirect()->route('student.landing')->with('error', 'Session invalid.');
        }
        if ($session->ended_at) {
            return redirect()->to(route('student.result') . '?token=' . urlencode($session->session_token));
        }
        if ($session->ip_address !== $request->ip()) {
            return redirect()->route('student.landing')->with('error', 'Session invalid.');
        }
        return view('student.final-photo-capture', [
            'quiz' => $session->quiz,
        ]);
    }

    /**
     * Store post-quiz face image (PostQuizCapture). Session resolved from HttpOnly session only.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['face_image' => 'required|string']);
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true]);
        }
        if ($session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        $imagePath = null;
        $postFaceImageHash = null;
        $data = $request->face_image;
        if (Str::startsWith($data, 'data:image')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $imageBytes = base64_decode($base64, true);
            if ($imageBytes !== false) {
                $postFaceImageHash = hash('sha256', $imageBytes);
            }
            if (CloudinaryService::isConfigured()) {
                $imagePath = CloudinaryService::uploadFromDataUrl($data, 'post_s' . $session->id);
            }
            if ($imagePath === null && $imageBytes !== false) {
                $imagePath = 'proctoring/post_' . $session->id . '_' . time() . '.jpg';
                Storage::disk('public')->put($imagePath, $imageBytes);
            }
            $session->update([
                'post_face_image' => $imagePath,
                'post_face_image_hash' => $postFaceImageHash,
                'post_face_captured_at' => now(),
            ]);
        }
        return response()->json(['success' => true]);
    }
}
