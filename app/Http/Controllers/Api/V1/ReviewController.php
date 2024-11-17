<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Movie;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Http\Requests\Api\V1\ReviewRequest;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
class ReviewController extends Controller
{
    use ApiResponses;
    // public function store(ReviewRequest $request)
    // {
    //     $review = Review::create($request->validated());

    //     return new ReviewResource($review);
    // }

    public function store(ReviewRequest $request, Movie $movie)
    {
            $review = $movie->reviews()->create([
            'user_id' => $request->user()->id,
            'author' => $request->user()->name,
            'comment' => $request->input('comment'),
            'rating' => $request->input('rating'),
        ]);

        return $this->success('Review posted successfully',new ReviewResource($review));

    }
     public function index($movieId)
    {
        $movie = Movie::find($movieId);

        if (!$movie) {

            return $this->error('Movie not found',404);
        }

        $reviews = $movie->reviews;

        return $this->success('Reviews posted successfully',$reviews);
    }
    public function getUserReviews(Request $request)
{
    // Get all reviews for the authenticated user
    $reviews = $request->user()->reviews;

    // Return reviews with a resource for better API structure (optional)
    return $this->success('User reviews fetched successfully', ReviewResource::collection($reviews));
}
}

