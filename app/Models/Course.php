<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['name', 'code', 'is_archived'];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    public function validIndices(): HasMany
    {
        return $this->hasMany(ValidIndex::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** Examiners assigned to this course (Super Admin assigns). */
    public function examiners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')->withTimestamps();
    }

    /** Class groups that have this course attached. */
    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_course')->withTimestamps();
    }

    /**
     * Resolve route model binding with authorization check for examiners.
     * Super Admins can access all courses, Examiners only their assigned ones.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $course = parent::resolveRouteBinding($value, $field);
        
        if (!$course) {
            return null;
        }
        
        // If user is authenticated and is an examiner, check access
        if (auth()->check() && session('admin_authenticated')) {
            $user = \App\Models\User::find(session('admin_user_id'));
            if ($user && $user->isExaminer() && !$user->isSuperAdmin()) {
                $courseIds = $user->assignedCourseIds();
                if (!empty($courseIds) && !in_array($course->id, $courseIds, true)) {
                    abort(403, 'You do not have access to this course.');
                }
            }
        }
        
        return $course;
    }
}
