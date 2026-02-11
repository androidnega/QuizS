<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\Setting;
use App\Models\User;

class ClassGroupPolicy
{
    /**
     * All staff (Super Admin and Examiners) can access class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return (int) $classGroup->examiner_id === (int) $user->id;
    }

    /**
     * Super Admin and examiners can create class groups unless admin has locked examiners. Super Admin assigns an examiner; examiners create for themselves.
     */
    public function create(User $user): bool
    {
        if (!$user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return Setting::getValue(Setting::KEY_LOCK_EXAMINER_CREATE_GROUP, '0') !== '1';
    }

    public function update(User $user, ClassGroup $classGroup): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return (int) $classGroup->examiner_id === (int) $user->id;
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        if (! $user->isStaff()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return (int) $classGroup->examiner_id === (int) $user->id;
    }
}
