<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ChallengeParticipant extends Model
{
    protected $fillable = [
        'challenge_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'status',
        'progress',
        'timer_started_at',
        'timer_ended_at',
        'timer_duration_seconds',
        'timer_is_active',
        'elapsed_time_seconds',
        'client_identifier'
    ];

    protected $casts = [
        'timer_started_at' => 'datetime',
        'timer_ended_at' => 'datetime',
        'timer_is_active' => 'boolean',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * Start the timer for this participant
     */
    public function startTimer()
    {
        $this->update([
            'timer_started_at' => now(),
            'timer_is_active' => true,
            'timer_ended_at' => null,
        ]);
    }

    /**
     * Stop the timer for this participant
     */
    public function stopTimer()
    {
        if ($this->timer_is_active && $this->timer_started_at) {
            // Calculate elapsed time since last resume/start
            $currentSessionSeconds = now()->diffInSeconds($this->timer_started_at);
            
            // Add to existing elapsed time (for multiple pause/resume cycles)
            $totalElapsedSeconds = $this->elapsed_time_seconds + $currentSessionSeconds;
            
            // Debug logging
            \Log::info('StopTimer Debug', [
                'participant_id' => $this->id,
                'timer_started_at' => $this->timer_started_at,
                'now' => now(),
                'current_session_seconds' => $currentSessionSeconds,
                'existing_elapsed_seconds' => $this->elapsed_time_seconds,
                'total_elapsed_seconds' => $totalElapsedSeconds,
            ]);
            
            // Ensure we don't save negative values
            $totalElapsedSeconds = max(0, $totalElapsedSeconds);
            
            $this->update([
                'timer_ended_at' => now(),
                'timer_is_active' => false,
                'elapsed_time_seconds' => $totalElapsedSeconds,
            ]);
        }
    }

    /**
     * Pause the timer for this participant
     */
    public function pauseTimer()
    {
        if ($this->timer_is_active && $this->timer_started_at) {
            // Calculate elapsed time since last resume/start
            $currentSessionSeconds = now()->diffInSeconds($this->timer_started_at);
            
            // Add to existing elapsed time (for multiple pause/resume cycles)
            $totalElapsedSeconds = $this->elapsed_time_seconds + $currentSessionSeconds;
            
            // Debug logging
            \Log::info('PauseTimer Debug', [
                'participant_id' => $this->id,
                'timer_started_at' => $this->timer_started_at,
                'now' => now(),
                'current_session_seconds' => $currentSessionSeconds,
                'existing_elapsed_seconds' => $this->elapsed_time_seconds,
                'total_elapsed_seconds' => $totalElapsedSeconds,
            ]);
            
            // Ensure we don't save negative values
            $totalElapsedSeconds = max(0, $totalElapsedSeconds);
            
            $this->update([
                'timer_is_active' => false,
                'elapsed_time_seconds' => $totalElapsedSeconds,
            ]);
        }
    }

    /**
     * Resume the timer for this participant
     */
    public function resumeTimer()
    {
        if (!$this->timer_is_active) {
            $this->update([
                'timer_started_at' => now(),
                'timer_is_active' => true,
            ]);
        }
    }

    /**
     * Reset the timer for this participant
     */
    public function resetTimer()
    {
        $this->update([
            'timer_started_at' => null,
            'timer_ended_at' => null,
            'timer_is_active' => false,
            'elapsed_time_seconds' => 0,
        ]);
    }

    /**
     * Get the current elapsed time in seconds
     */
    public function getCurrentElapsedTime()
    {
        $elapsed = $this->elapsed_time_seconds;
        
        // If timer is actively running, add current elapsed time since last start/resume
        if ($this->timer_is_active && $this->timer_started_at) {
            $currentSessionSeconds = now()->diffInSeconds($this->timer_started_at);
            $elapsed += max(0, $currentSessionSeconds);
        }
        // If timer is stopped, just return the stored elapsed time
        // (no need to recalculate since it's already stored correctly)
        
        // Ensure we don't return negative values
        return max(0, $elapsed);
    }

    /**
     * Get formatted elapsed time
     */
    public function getFormattedElapsedTime()
    {
        $seconds = $this->getCurrentElapsedTime();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Check if timer is running
     */
    public function isTimerRunning()
    {
        return $this->timer_is_active && $this->timer_started_at;
    }

    /**
     * Check if timer has been started at least once
     */
    public function hasStartedTimer()
    {
        return $this->timer_started_at !== null;
    }
}
