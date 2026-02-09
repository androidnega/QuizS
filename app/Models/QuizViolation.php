<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizViolation extends Model
{
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = ['quiz_session_id', 'type', 'severity', 'metadata', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public static function types(): array
    {
        return ['blur', 'multiple_ip', 'copy_paste', 'right_click', 'face_mismatch', 'tab_switch', 'window_resize', 'other'];
    }

    /**
     * Classify violation type into severity.
     * Critical (auto-submit on first): copy_paste, multiple_ip.
     * Warning (no auto-submit; right-click only warns): right_click, blur, tab_switch, other.
     * Auto-submit on tab switch is handled separately when blur/tab_switch count reaches threshold.
     */
    public static function severityForType(string $type): string
    {
        return in_array($type, ['copy_paste', 'multiple_ip'], true)
            ? self::SEVERITY_CRITICAL
            : self::SEVERITY_WARNING;
    }
}
