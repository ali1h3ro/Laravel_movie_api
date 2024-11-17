<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\MovieResource;
use App\Traits\ApiResponses;
use App\Models\Movie;
use App\Http\Controllers\Controller;

use App\Http\Requests\Api\V1\SearchMoviesRequest;
class MovieController extends Controller
{
    use ApiResponses;
    public function show($id)
    {
        $movie = Movie::with('reviews')->find($id);

        if (!$movie) {
        return $this->error('Movie not found',404);

        }

        return $this->success('Movies returned successfully',new MovieResource($movie));
    }

    public function index()
    {
        return $this->success('Movies returned successfully',MovieResource::collection(Movie::all()));

    }
    public function search(SearchMoviesRequest $request)
    {

        // Validate the request
        $validated = $request->validated();

        // Initialize the query builder
        $query = Movie::query();

        // Apply filters based on input
        $query->when($validated['query'] ?? null, function ($q, $searchTerm) {
            $q->search($searchTerm);  // Apply search by title or description
        });

        $query->when($validated['year'] ?? null, fn($q, $year) => $q->year($year));  // Apply year filter
        $query->when($validated['director'] ?? null, fn($q, $director) => $q->director($director));  // Apply director filter

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $query->sort($sortBy, $order);

        // Apply pagination
        $perPage = $validated['per_page'] ?? 15;
        $movies = $query->paginate($perPage)->appends($request->query());

        // Return the results with the metadata for filters applied
        return MovieResource::collection($movies)
            ->additional([
                'meta' => [
                    'filters' => array_filter([
                        'query' => $validated['query'] ?? null,
                        'year' => $validated['year'] ?? null,
                        'director' => $validated['director'] ?? null,
                        'sort_by' => $sortBy,
                        'order' => $order
                    ])
                ]
            ]);
    }
}
