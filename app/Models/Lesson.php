<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content',
        'video_url',
        'audio_url',
        'file_url',
        'type',
        'duration_minutes',
        'order_index',
        'is_free_preview',
        'attachments',
        'quiz_data',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'order_index' => 'integer',
        'is_free_preview' => 'boolean',
        'attachments' => 'array',
        'quiz_data' => 'array',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function scopeByOrder($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }

    public function getNextLesson()
    {
        return $this->course->lessons()
            ->where('order_index', '>', $this->order_index)
            ->orderBy('order_index')
            ->first();
    }

    public function getPreviousLesson()
    {
        return $this->course->lessons()
            ->where('order_index', '<', $this->order_index)
            ->orderBy('order_index', 'desc')
            ->first();
    }
}
