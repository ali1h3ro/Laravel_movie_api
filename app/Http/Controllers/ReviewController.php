<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use App\Http\Requests\ReviewRequest;
class ReviewController extends Controller
{
    public function store(ReviewRequest $request)
    {
        $review = Review::create($request->validated());

        return new ReviewResource($review);
    }
}
