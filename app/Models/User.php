<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Roles: super_admin, examiner. Students use index only (no account). */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_EXAMINER = 'examiner';

    protected $fillable = ['username', 'email', 'index_number', 'name', 'course_id', 'role', 'password', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Passwords are stored hashed (bcrypt) and never in plain text.
     * Applies to both Super Admin and Examiner accounts.
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Courses assigned to this examiner (Super Admin assigns). */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user')->withTimestamps();
    }

    /** Class groups owned by this examiner. */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'examiner_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isExaminer(): bool
    {
        return $this->role === self::ROLE_EXAMINER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EXAMINER], true);
    }

    /** IDs of courses assigned to this examiner (empty for super_admin = all). */
    public function assignedCourseIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Course::where('is_archived', false)->pluck('id')->all();
        }
        return $this->courses()->where('is_archived', false)->pluck('courses.id')->all();
    }

    /** IDs of class groups owned by this examiner (empty for super_admin = all). */
    public function classGroupIds(): array
    {
        if ($this->isSuperAdmin()) {
            return ClassGroup::pluck('id')->all();
        }
        return $this->classGroups()->pluck('id')->all();
    }

    /** Full URL for avatar (Cloudinary URL or local storage path). */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }
        return asset('storage/' . $this->avatar);
    }
}
