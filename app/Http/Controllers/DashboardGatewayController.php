<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardGatewayController extends Controller
{
    /**
     * Unified dashboard: students see student dashboard; staff see admin/examiner dashboard.
     */
    public function __invoke(): View|RedirectResponse
    {
        if (session('student_id')) {
            return app(StudentDashboardController::class)->index();
        }
        return app(AdminDashboardController::class)->index();
    }
}
