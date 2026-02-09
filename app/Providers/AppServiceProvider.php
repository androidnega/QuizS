<?php

namespace App\Providers;

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

        View::composer('*', function ($view): void {
            if (request()->routeIs('admin.*')) {
                $view->with('staffPrefix', 'admin');
            } elseif (request()->routeIs('examiner.*')) {
                $view->with('staffPrefix', 'examiner');
            }
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
