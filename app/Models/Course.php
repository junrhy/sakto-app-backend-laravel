<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'thumbnail_url',
        'video_url',
        'difficulty',
        'status',
        'is_featured',
        'is_free',
        'price',
        'currency',
        'duration_minutes',
        'lessons_count',
        'enrolled_count',
        'tags',
        'requirements',
        'learning_outcomes',
        'instructor_name',
        'instructor_bio',
        'instructor_avatar',
        'category',
        'subcategory',
        'client_identifier',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'lessons_count' => 'integer',
        'enrolled_count' => 'integer',
        'tags' => 'array',
        'requirements' => 'array',
        'learning_outcomes' => 'array',
    ];

    /**
     * Scope to filter by client identifier.
     */
    public function scopeByClient(Builder $query, string $clientIdentifier): Builder
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by difficulty.
     */
    public function scopeByDifficulty(Builder $query, string $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter featured courses.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter free courses.
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope to filter paid courses.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    /**
     * Get the enrollments for this course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * Get the lessons for this course.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Update the enrolled count for this course.
     */
    public function updateEnrolledCount(): void
    {
        $this->update([
            'enrolled_count' => $this->enrollments()
                ->where('status', '!=', 'cancelled')
                ->count()
        ]);
    }

    /**
     * Update the lessons count for this course.
     */
    public function updateLessonsCount(): void
    {
        $this->update([
            'lessons_count' => $this->lessons()->count()
        ]);
    }

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return 'Free';
        }
        
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDurationAttribute(): string
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

    /**
     * Check if the course is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the course is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the course is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Get the course URL.
     */
    public function getUrlAttribute(): string
    {
        return route('courses.show', $this->slug);
    }

    /**
     * Get the thumbnail URL or default image.
     */
    public function getThumbnailUrlAttribute($value): string
    {
        return $value ?: '/images/default-course-thumbnail.jpg';
    }

    /**
     * Get the instructor avatar URL or default image.
     */
    public function getInstructorAvatarAttribute($value): string
    {
        return $value ?: '/images/default-avatar.jpg';
    }
}
