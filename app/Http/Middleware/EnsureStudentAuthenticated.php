<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('admin_authenticated')) {
            return redirect()->route('dashboard');
        }

        $studentId = session('student_id');
        if (!$studentId) {
            return redirect()->guest(route('student.account.login.form'))
                ->with('error', 'Please log in to access your dashboard.');
        }

        $student = Student::find($studentId);
        if (!$student) {
            session()->forget(['student_id', 'student_index']);
            return redirect()->guest(route('student.account.login.form'))
                ->with('error', 'Session invalid. Please log in again.');
        }

        auth()->setUser($student);

        return $next($request);
    }
}
