<?php

namespace App\Models;

use App\Casts\EncryptedNullable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'quiz_id', 'text', 'type', 'options', 'correct_answer',
        'topic', 'source', 'points', 'explanation_wrong', 'explanation_correct',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'text' => EncryptedNullable::class,
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
