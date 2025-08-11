<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'course_enrollment_id',
        'lesson_id',
        'status',
        'time_watched_seconds',
        'completion_percentage',
        'started_at',
        'completed_at',
        'quiz_answers',
        'quiz_score',
        'notes',
    ];

    protected $casts = [
        'time_watched_seconds' => 'integer',
        'completion_percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'quiz_answers' => 'array',
        'quiz_score' => 'integer',
    ];

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completion_percentage' => 100,
            'completed_at' => now(),
        ]);

        // Update enrollment progress
        $this->enrollment->updateProgress();
    }

    public function updateProgress($completionPercentage, $timeWatched = null)
    {
        $updates = [
            'completion_percentage' => $completionPercentage,
        ];

        if ($timeWatched !== null) {
            $updates['time_watched_seconds'] = $timeWatched;
        }

        if ($completionPercentage >= 100) {
            $updates['status'] = 'completed';
            $updates['completed_at'] = now();
        } elseif ($completionPercentage > 0) {
            $updates['status'] = 'in_progress';
        }

        $this->update($updates);

        // Update enrollment progress if lesson is completed
        if ($completionPercentage >= 100) {
            $this->enrollment->updateProgress();
        }
    }

    public function submitQuiz($answers, $score)
    {
        $this->update([
            'quiz_answers' => $answers,
            'quiz_score' => $score,
            'status' => $score >= 70 ? 'completed' : 'failed',
            'completion_percentage' => $score >= 70 ? 100 : 0,
            'completed_at' => $score >= 70 ? now() : null,
        ]);

        if ($score >= 70) {
            $this->enrollment->updateProgress();
        }
    }

    public function getFormattedTimeWatchedAttribute()
    {
        if (!$this->time_watched_seconds) {
            return '0m';
        }

        $hours = floor($this->time_watched_seconds / 3600);
        $minutes = floor(($this->time_watched_seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }
}
