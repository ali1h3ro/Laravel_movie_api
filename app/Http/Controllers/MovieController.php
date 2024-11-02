<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Http\Resources\MovieResource;


class MovieController extends Controller
{
    public function show()
    {
        return MovieResource::collection(Movie::all());
    }

    public function index($id)
    {
        $movie = Movie::with('reviews')->find($id);

        if (!$movie) {
            return response()->json(['message' => 'Movie not found'], 404);
        }

        return new MovieResource($movie);
    }
}
