<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class RunMigrationsController extends Controller
{
    private const SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Run pending Laravel migrations via URL with a secret key.
     * Visit: /run-migrations?key=YOUR_SECRET
     */
    public function __invoke(Request $request): Response
    {
        if ($request->query('key') !== self::SECRET) {
            return response('Invalid or missing key. Use: /run-migrations?key=YOUR_SECRET', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $output = "QuizSnap: Run pending Laravel migrations\n";
        $output .= "=======================================\n\n";

        try {
            $output .= "Step 1: Run migrate --force...\n";
            Artisan::call('migrate', ['--force' => true]);
            $output .= trim(Artisan::output()) . "\n\n";

            $output .= "Step 2: Clear caches...\n";
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $output .= "Caches cleared.\n\n";

            $output .= "=======================================\n";
            $output .= "SUCCESS: Pending migrations executed.\n";
        } catch (\Throwable $e) {
            $output .= "ERROR: " . $e->getMessage() . "\n";
            $output .= $e->getTraceAsString();
        }

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
