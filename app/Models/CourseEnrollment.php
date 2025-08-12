<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'contact_id',
        'student_name',
        'student_email',
        'student_phone',
        'status',
        'payment_status',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'enrolled_at',
        'completed_at',
        'expires_at',
        'progress_percentage',
        'lessons_completed',
        'certificate_data',
        'client_identifier',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'progress_percentage' => 'integer',
        'lessons_completed' => 'integer',
        'certificate_data' => 'array',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope to filter by client identifier.
     */
    public function scopeByClient(Builder $query, string $clientIdentifier): Builder
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by contact.
     */
    public function scopeByContact(Builder $query, string $contactId): Builder
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by payment status.
     */
    public function scopeByPaymentStatus(Builder $query, string $paymentStatus): Builder
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Get the course for this enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lesson progress for this enrollment.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Check if the enrollment is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the enrollment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the enrollment is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if the payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if the payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if the payment failed.
     */
    public function isPaymentFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Check if the payment was refunded.
     */
    public function isPaymentRefunded(): bool
    {
        return $this->payment_status === 'refunded';
    }

    /**
     * Update the progress percentage.
     */
    public function updateProgress(): void
    {
        $totalLessons = $this->course->lessons()->count();
        if ($totalLessons > 0) {
            $completedLessons = $this->lessonProgress()
                ->where('status', 'completed')
                ->count();
            
            $progressPercentage = round(($completedLessons / $totalLessons) * 100);
            
            $this->update([
                'lessons_completed' => $completedLessons,
                'progress_percentage' => $progressPercentage
            ]);

            // If progress reaches 100%, mark enrollment as completed and generate certificate
            if ($progressPercentage >= 100 && $this->status !== 'completed') {
                $this->markAsCompleted();
                $this->generateCertificate();
            }
        }
    }

    /**
     * Mark the enrollment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100
        ]);
    }

    /**
     * Generate certificate data.
     */
    public function generateCertificate(): array
    {
        $certificateData = [
            'certificate_id' => 'CERT-' . strtoupper(uniqid()),
            'student_name' => $this->student_name,
            'course_title' => $this->course->title,
            'completion_date' => $this->completed_at->format('Y-m-d'),
            'instructor_name' => $this->course->instructor_name,
            'course_duration' => $this->course->formatted_duration,
            'issued_at' => now()->format('Y-m-d H:i:s'),
        ];

        $this->update(['certificate_data' => $certificateData]);

        return $certificateData;
    }

    /**
     * Get the formatted amount paid.
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        if (!$this->amount_paid) {
            return 'Free';
        }
        
        return $this->course->currency . ' ' . number_format($this->amount_paid, 2);
    }

    /**
     * Get the enrollment duration.
     */
    public function getEnrollmentDurationAttribute(): string
    {
        $start = $this->enrolled_at;
        $end = $this->completed_at ?: now();
        
        $diff = $start->diff($end);
        
        if ($diff->days > 0) {
            return $diff->days . ' days';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours';
        } else {
            return $diff->i . ' minutes';
        }
    }
}
