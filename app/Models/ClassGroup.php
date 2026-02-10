<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassGroup extends Model
{
    protected $fillable = ['name', 'examiner_id'];

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(ClassGroupStudent::class, 'class_group_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'class_group_course')->withTimestamps();
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'class_group_id');
    }

    /** Whether this class group has at least one student (required for quiz creation). */
    public function hasStudents(): bool
    {
        return $this->students()->exists();
    }

    /**
     * Resolve route model binding with authorization check for examiners.
     * Super Admins can access all class groups, Examiners only their own.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $classGroup = parent::resolveRouteBinding($value, $field);
        
        if (!$classGroup) {
            return null;
        }
        
        // If user is authenticated and is an examiner, check access
        if (auth()->check() && session('admin_authenticated')) {
            $user = \App\Models\User::find(session('admin_user_id'));
            if ($user && $user->isExaminer() && !$user->isSuperAdmin()) {
                if ($classGroup->examiner_id !== $user->id) {
                    abort(403, 'You do not have access to this class group.');
                }
            }
        }
        
        return $classGroup;
    }
}
