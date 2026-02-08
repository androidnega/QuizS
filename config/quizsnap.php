<?php

return [
    /*
    | Staff (admin/examiner) credentials are stored in the `users` table only.
    | To create the first accounts, set ADMIN_USERNAME, ADMIN_PASSWORD (and optionally
    | EXAMINER_*) in .env and run: php artisan db:seed
    | Set ADMIN_* and EXAMINER_* in .env, then run: php artisan db:seed
    */

    'ai' => [
        'max_generation_per_quiz' => (int) env('QUIZSNAP_AI_MAX_PER_QUIZ', 100),
    ],
];
