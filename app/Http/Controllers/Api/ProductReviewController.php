<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductReviewController extends Controller
{
    /**
     * Get all reviews for a product
     */
    public function index(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        
        $query = $product->reviews()->with(['user:id,name,email']);
        
        // Filter by approval status
        if ($request->has('approved')) {
            if ($request->boolean('approved')) {
                $query->approved();
            }
        } else {
            // Default to approved reviews only
            $query->approved();
        }
        
        // Filter by rating
        if ($request->has('rating')) {
            $rating = (int) $request->rating;
            if ($rating >= 1 && $rating <= 5) {
                $query->byRating($rating);
            }
        }
        
        // Filter by verified purchase
        if ($request->boolean('verified_purchase')) {
            $query->verifiedPurchase();
        }
        
        // Sort options
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'helpful':
                $query->mostHelpful();
                break;
            case 'highest_rating':
                $query->highestRating();
                break;
            case 'lowest_rating':
                $query->lowestRating();
                break;
            case 'recent':
            default:
                $query->recent();
                break;
        }
        
        $reviews = $query->paginate($request->get('per_page', 10));
        
        return response()->json([
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'summary' => [
                'average_rating' => $product->average_rating,
                'total_reviews' => $product->reviews_count,
                'rating_distribution' => $product->rating_distribution,
            ]
        ]);
    }

    /**
     * Store a new review
     */
    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:10|max:2000',
            'rating' => 'required|integer|between:1,5',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'client_identifier' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user has already reviewed this product
        $existingReview = $product->reviews()->where('user_id', $request->user_id)->first();
        if ($existingReview) {
            return response()->json([
                'error' => 'You have already reviewed this product'
            ], 409);
        }

        $data = $validator->validated();
        
        // Check if user has purchased this product (for verified purchase status)
        $isVerifiedPurchase = $this->checkVerifiedPurchase($product, $request->user_id);
        
        $review = $product->reviews()->create([
            'user_id' => $request->user_id,
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'rating' => $data['rating'],
            'images' => $data['images'] ?? [],
            'is_verified_purchase' => $isVerifiedPurchase,
            'is_approved' => false, // Requires admin approval
        ]);

        $review->load('user:id,name,email');

        return response()->json([
            'message' => 'Review submitted successfully and is pending approval',
            'review' => $review
        ], 201);
    }

    /**
     * Show a specific review
     */
    public function show(string $productId, string $reviewId): JsonResponse
    {
        $review = ProductReview::with(['user:id,name,email', 'product:id,name'])
            ->where('product_id', $productId)
            ->findOrFail($reviewId);

        return response()->json(['review' => $review]);
    }

    /**
     * Update a review
     */
    public function update(Request $request, string $productId, string $reviewId): JsonResponse
    {
        $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
        
        // Check if user owns the review or is admin
        if ($review->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:10|max:2000',
            'rating' => 'required|integer|between:1,5',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // If review was approved, it needs re-approval after update
        if ($review->is_approved) {
            $data['is_approved'] = false;
        }
        
        $review->update($data);
        $review->load('user:id,name,email');

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, string $productId, string $reviewId): JsonResponse
    {
        $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
        
        // Check if user owns the review or is admin
        if ($review->user_id !== $request->user_id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Vote on a review (helpful/unhelpful)
     */
    public function vote(Request $request, string $productId, string $reviewId): JsonResponse
    {
        $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
        
        $validator = Validator::make($request->all(), [
            'vote_type' => ['required', Rule::in(['helpful', 'unhelpful'])],
            'client_identifier' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->user_id;
        $voteType = $request->vote_type;

        // Check if user has already voted
        $hasVotedHelpful = $review->hasUserVotedHelpful($userId);
        $hasVotedUnhelpful = $review->hasUserVotedUnhelpful($userId);

        if ($voteType === 'helpful') {
            if ($hasVotedHelpful) {
                // Remove helpful vote
                $review->removeHelpfulVote($userId);
                $message = 'Helpful vote removed';
            } else {
                // Add helpful vote and remove unhelpful if exists
                if ($hasVotedUnhelpful) {
                    $review->removeUnhelpfulVote($userId);
                }
                $review->addHelpfulVote($userId);
                $message = 'Marked as helpful';
            }
        } else {
            if ($hasVotedUnhelpful) {
                // Remove unhelpful vote
                $review->removeUnhelpfulVote($userId);
                $message = 'Unhelpful vote removed';
            } else {
                // Add unhelpful vote and remove helpful if exists
                if ($hasVotedHelpful) {
                    $review->removeHelpfulVote($userId);
                }
                $review->addUnhelpfulVote($userId);
                $message = 'Marked as unhelpful';
            }
        }

        return response()->json([
            'message' => $message,
            'helpful_votes' => $review->helpful_votes_count,
            'unhelpful_votes' => $review->unhelpful_votes_count,
            'user_voted_helpful' => $review->hasUserVotedHelpful($userId),
            'user_voted_unhelpful' => $review->hasUserVotedUnhelpful($userId),
        ]);
    }

    /**
     * Admin: Approve a review
     */
    public function approve(Request $request, string $productId, string $reviewId): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
        $review->approve();

        return response()->json([
            'message' => 'Review approved successfully',
            'review' => $review
        ]);
    }

    /**
     * Admin: Feature/Unfeature a review
     */
    public function toggleFeature(Request $request, string $productId, string $reviewId): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = ProductReview::where('product_id', $productId)->findOrFail($reviewId);
        
        if ($review->is_featured) {
            $review->unfeature();
            $message = 'Review unfeatured successfully';
        } else {
            $review->feature();
            $message = 'Review featured successfully';
        }

        return response()->json([
            'message' => $message,
            'review' => $review
        ]);
    }

    /**
     * Get review statistics for a product
     */
    public function statistics(string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        
        return response()->json([
            'average_rating' => $product->average_rating,
            'total_reviews' => $product->reviews_count,
            'rating_distribution' => $product->rating_distribution,
            'verified_purchase_count' => $product->approvedReviews()->verifiedPurchase()->count(),
            'featured_reviews_count' => $product->featuredReviews()->count(),
        ]);
    }

    /**
     * Check if user has purchased the product (for verified purchase status)
     */
    private function checkVerifiedPurchase(Product $product, int $userId): bool
    {
        // This is a placeholder implementation
        // You would typically check against your orders table
        // For now, we'll return false as a default
        return false;
    }
}
