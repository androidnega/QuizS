<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;

class ClassGroupPolicy
{
    /**
     * Super Admin can do everything. Examiner can access only their own class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $classGroup->examiner_id === $user->id;
    }

    /**
     * Super Admin and examiners can create class groups. Super Admin assigns an examiner; examiners create for themselves.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $classGroup->examiner_id === $user->id;
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $classGroup->examiner_id === $user->id;
    }
}
