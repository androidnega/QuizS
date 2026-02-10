<?php

namespace App\Providers;

use App\Models\QuizSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->ensureSqliteDatabaseExists();

        // Resolve quizSession by id so session/student data page always loads (no scoping conflict)
        Route::bind('quizSession', function (string $value) {
            return QuizSession::findOrFail($value);
        });

        View::composer('*', function ($view): void {
            if (request()->routeIs('admin.*')) {
                $view->with('staffPrefix', 'admin');
            } elseif (request()->routeIs('examiner.*')) {
                $view->with('staffPrefix', 'examiner');
            }
        });

        View::composer('layouts.student-dashboard', function ($view): void {
            $user = auth()->user();
            $student = null;
            $greeting = 'Hello';
            if ($user instanceof \App\Models\Student) {
                $student = $user;
            } elseif (session('student_id')) {
                $student = \App\Models\Student::find(session('student_id'));
            }
            if ($student) {
                $hour = (int) now()->format('G');
                if ($hour >= 5 && $hour < 12) {
                    $greeting = 'Good morning';
                } elseif ($hour >= 12 && $hour < 17) {
                    $greeting = 'Good afternoon';
                } else {
                    $greeting = 'Good evening';
                }
            }
            $view->with(compact('student', 'greeting'));
        });
    }

    /**
     * When using SQLite, ensure the database file exists (create if missing).
     * Prevents "Database file does not exist" on deploy when DB_DATABASE path is relative or file was not committed.
     */
    protected function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');
        if (empty($path)) {
            return;
        }

        // Resolve relative path (e.g. "database/database.sqlite") to absolute so CWD does not matter
        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = base_path($path);
            config(['database.connections.sqlite.database' => $path]);
        }

        if (! file_exists($path)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @touch($path);
        }
    }
}
