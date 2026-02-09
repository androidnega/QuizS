<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student implements Authenticatable
{
    protected $table = 'students';

    protected $fillable = ['index_number', 'phone_contact', 'student_name'];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /** Class group memberships (this index in various groups). */
    public function classGroupStudents(): HasMany
    {
        return $this->hasMany(ClassGroupStudent::class, 'index_number', 'index_number');
    }

    /** Quiz sessions where this student (by index) took a quiz. */
    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class, 'student_index', 'index_number');
    }

    public function hasPhone(): bool
    {
        return $this->phone_contact !== null && trim($this->phone_contact) !== '';
    }

    /** Display name: student_name or index_number. */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->student_name ?? '') !== ''
            ? $this->student_name
            : $this->index_number;
    }
}
