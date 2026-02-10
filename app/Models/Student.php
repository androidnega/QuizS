<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model implements Authenticatable
{
    protected $table = 'students';

    protected $fillable = ['index_number', 'index_number_hash', 'phone_contact', 'student_name'];

    /**
     * Normalize index for hashing and comparison (trim + lowercase).
     */
    public static function normalizeIndex(?string $index): string
    {
        return $index !== null ? strtolower(trim($index)) : '';
    }

    /**
     * SHA-256 hash of normalized index number. Use for lookups; store in index_number_hash.
     */
    public static function hashIndexNumber(?string $index): string
    {
        return hash('sha256', self::normalizeIndex($index));
    }

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

    /** First name only (first word of student_name, or index_number if no name). */
    public function getFirstNameAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return $this->index_number;
        }
        $first = explode(' ', $name, 2)[0] ?? '';
        return $first !== '' ? $first : $this->index_number;
    }

    /** Initials for avatar placeholder (e.g. "Emmanuel Kofi" → "EK"). */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return strtoupper(substr($this->index_number, 0, 2));
        }
        $words = preg_split('/\s+/', $name, 3);
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
}
