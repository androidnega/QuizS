<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['name', 'code', 'is_archived'];

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            // Force course name to uppercase
            if (isset($course->name)) {
                $course->name = strtoupper(trim($course->name));
            }
        });
    }

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
     * Resolve route model binding. Access control is handled by course middleware/policies.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field);
    }
}
