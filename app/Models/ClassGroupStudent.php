<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassGroupStudent extends Model
{
    protected $fillable = ['class_group_id', 'index_number', 'student_name'];

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }
}
