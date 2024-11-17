<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FetchMoviesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    private const CACHE_KEY = 'tmdb_api_last_request';
    private const API_RATE_LIMIT = 40;
    private const API_RATE_WINDOW = 10;
    private const MAX_MOVIES = 3000;
    private const MOVIES_PER_PAGE = 20;

    public function handle()
    {
        try {
            $this->checkRateLimit();
            Log::info('FetchMoviesJob started');

            $page = 1;
            $totalPages = 1;
            $moviesFetched = 0;
            $maxPages = ceil(self::MAX_MOVIES / self::MOVIES_PER_PAGE);

            do {
                $movies = $this->fetchMoviesPage($page);

                if (!$movies) {
                    break;
                }

                $totalPages = $movies['total_pages'] ?? 1;
                $moviesFetched += count($movies['results'] ?? []);
                $this->processMovies($movies['results'] ?? []);

                $page++;
                $this->checkRateLimit();
            } while ($page <= $totalPages && $page <= $maxPages && $moviesFetched < self::MAX_MOVIES);

            Log::info('FetchMoviesJob completed successfully');
        } catch (Throwable $e) {
            Log::error('FetchMoviesJob failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function fetchMoviesPage(int $page): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.tmdb.api_token'),
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/movie/popular', [
            'page' => $page,
            'language' => 'en-US'
        ]);

        if (!$response->successful()) {
            Log::error('TMDB API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }

    private function processMovies(array $movies): void
    {
        $fetchTime = Carbon::now();

        foreach ($movies as $movieData) {
            if (empty($movieData['id'])) {
                Log::warning('Missing external_id for movie', ['data' => $movieData]);
                continue;
            }

            try {
                $this->processMovie($movieData, $fetchTime);
            } catch (Throwable $e) {
                Log::error('Failed to process movie', [
                    'movie' => $movieData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function processMovie(array $movieData, Carbon $fetchTime): void
    {
        Log::info('Processing movie', [
            'external_id' => $movieData['id'],
            'title' => $movieData['title']
        ]);

        $movieAttributes = [
            'title' => $movieData['title'],
            'description' => $movieData['overview'] ?? null,
            'external_id' => (string)$movieData['id'],
            'year' => isset($movieData['release_date']) ? Carbon::parse($movieData['release_date'])->year : null
        ];

        $movie = Movie::updateOrCreate(
            ['external_id' => (string)$movieData['id']],
            $movieAttributes
        );

        $this->checkRateLimit();
        $details = $this->fetchMovieDetails($movieData['id']);

        if ($details) {
            $movie->director = $this->extractDirector($details['credits']['crew'] ?? []);
            $movie->save();
        }

        $this->updateMovieLogs($movie, $movieData, $fetchTime);
    }

    private function fetchMovieDetails(int $movieId): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.tmdb.api_token'),
            'Accept' => 'application/json',
        ])->get("https://api.themoviedb.org/3/movie/{$movieId}", [
            'append_to_response' => 'credits'
        ]);

        return $response->successful() ? $response->json() : null;
    }

    private function extractDirector(array $crew): ?string
    {
        foreach ($crew as $member) {
            if ($member['job'] === 'Director') {
                return $member['name'];
            }
        }
        return null;
    }

    private function updateMovieLogs(Movie $movie, array $movieData, Carbon $fetchTime): void
    {
        $logEntry = [
            'fetched_at' => $fetchTime->toIso8601String(),
            'completed_at' => Carbon::now()->toIso8601String(),
            'status' => 'success',
            'api_data' => $movieData,
        ];

        $logs = $movie->fetch_logs ?? [];
        array_push($logs, $logEntry);

        if (count($logs) > 10) {
            $logs = array_slice($logs, -10);
        }

        $movie->fetch_logs = $logs;
        $movie->save();
    }

    private function checkRateLimit(): void
    {
        $requests = Cache::get(self::CACHE_KEY, 0);

        if ($requests >= self::API_RATE_LIMIT) {
            $sleepTime = self::API_RATE_WINDOW;
            Log::info("Rate limit reached, sleeping for {$sleepTime} seconds");
            sleep($sleepTime);
            Cache::put(self::CACHE_KEY, 1, self::API_RATE_WINDOW);
        } else {
            Cache::put(self::CACHE_KEY, $requests + 1, self::API_RATE_WINDOW);
        }
    }
}
