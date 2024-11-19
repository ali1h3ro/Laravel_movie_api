<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class MovieSeeder extends Seeder
{
    public function run()
    {

        // Fetch popular movies from TMDb API
        $response = Http::get('https://api.themoviedb.org/3/movie/popular', [
            'api_key' => env('TMDB_API_KEY'),
            'language' => 'en-US',
            'page' => 1,
        ]);

        if ($response->successful()) {
            $movies = $response->json()['results'];

            foreach ($movies as $movieData) {
                // Create a new Movie record
                $movie = Movie::create([
                    'title' => $movieData['title'] ?? 'N/A',
                    'description' => $movieData['overview'] ?? 'N/A',
                    'director' => 'N/A',
                    'year' => isset($movieData['release_date']) ? substr($movieData['release_date'], 0, 4) : null,
                ]);

                // Generate fake reviews for each movie
                Review::factory(rand(3, 5))->create(['movie_id' => $movie->id]);
            }
        } else {
            $this->command->error('Failed to fetch data from TMDb.');
        }
    }
}
