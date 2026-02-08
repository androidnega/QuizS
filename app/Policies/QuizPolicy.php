<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Super Admin can do everything. Examiner can access only quizzes under their class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (! $quiz->class_group_id) {
            return false;
        }
        $classGroupIds = $user->classGroupIds();
        return in_array($quiz->class_group_id, $classGroupIds, true);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->view($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->view($user, $quiz);
    }
}
