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
     * Resolve route model binding. Access control is handled by ClassGroupPolicy.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field);
    }
}
