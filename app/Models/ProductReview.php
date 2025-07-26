<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'reviewer_name',
        'reviewer_email',
        'title',
        'content',
        'rating',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'images',
        'helpful_votes',
        'unhelpful_votes',
        'approved_at',
        'featured_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'helpful_votes' => 'array',
        'unhelpful_votes' => 'array',
        'approved_at' => 'datetime',
        'featured_at' => 'datetime',
    ];

    protected $dates = [
        'approved_at',
        'featured_at',
    ];

    protected $appends = [
        'helpful_votes_count',
        'unhelpful_votes_count',
        'total_votes_count',
    ];

    /**
     * Get the product that owns the review.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only approved reviews.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get only featured reviews.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get reviews by rating.
     */
    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to get verified purchase reviews.
     */
    public function scopeVerifiedPurchase(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by most helpful.
     */
    public function scopeMostHelpful(Builder $query): Builder
    {
        return $query->orderByRaw('JSON_LENGTH(helpful_votes) DESC');
    }

    /**
     * Scope to order by highest rating.
     */
    public function scopeHighestRating(Builder $query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope to order by lowest rating.
     */
    public function scopeLowestRating(Builder $query): Builder
    {
        return $query->orderBy('rating', 'asc');
    }

    /**
     * Get the helpful votes count.
     */
    public function getHelpfulVotesCountAttribute(): int
    {
        return is_array($this->helpful_votes) ? count($this->helpful_votes) : 0;
    }

    /**
     * Get the unhelpful votes count.
     */
    public function getUnhelpfulVotesCountAttribute(): int
    {
        return is_array($this->unhelpful_votes) ? count($this->unhelpful_votes) : 0;
    }

    /**
     * Get the total votes count.
     */
    public function getTotalVotesCountAttribute(): int
    {
        return $this->helpful_votes_count + $this->unhelpful_votes_count;
    }

    /**
     * Check if a user has voted helpful.
     */
    public function hasUserVotedHelpful(int $userId): bool
    {
        return is_array($this->helpful_votes) && in_array($userId, $this->helpful_votes);
    }

    /**
     * Check if a user has voted unhelpful.
     */
    public function hasUserVotedUnhelpful(int $userId): bool
    {
        return is_array($this->unhelpful_votes) && in_array($userId, $this->unhelpful_votes);
    }

    /**
     * Add helpful vote from user.
     */
    public function addHelpfulVote(int $userId): void
    {
        $votes = is_array($this->helpful_votes) ? $this->helpful_votes : [];
        if (!in_array($userId, $votes)) {
            $votes[] = $userId;
            $this->update(['helpful_votes' => $votes]);
        }
    }

    /**
     * Add unhelpful vote from user.
     */
    public function addUnhelpfulVote(int $userId): void
    {
        $votes = is_array($this->unhelpful_votes) ? $this->unhelpful_votes : [];
        if (!in_array($userId, $votes)) {
            $votes[] = $userId;
            $this->update(['unhelpful_votes' => $votes]);
        }
    }

    /**
     * Remove helpful vote from user.
     */
    public function removeHelpfulVote(int $userId): void
    {
        $votes = is_array($this->helpful_votes) ? $this->helpful_votes : [];
        $votes = array_diff($votes, [$userId]);
        $this->update(['helpful_votes' => array_values($votes)]);
    }

    /**
     * Remove unhelpful vote from user.
     */
    public function removeUnhelpfulVote(int $userId): void
    {
        $votes = is_array($this->unhelpful_votes) ? $this->unhelpful_votes : [];
        $votes = array_diff($votes, [$userId]);
        $this->update(['unhelpful_votes' => array_values($votes)]);
    }

    /**
     * Approve the review.
     */
    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    /**
     * Feature the review.
     */
    public function feature(): void
    {
        $this->update([
            'is_featured' => true,
            'featured_at' => now(),
        ]);
    }

    /**
     * Unfeature the review.
     */
    public function unfeature(): void
    {
        $this->update([
            'is_featured' => false,
            'featured_at' => null,
        ]);
    }
}
