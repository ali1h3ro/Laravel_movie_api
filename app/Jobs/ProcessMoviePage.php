<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\MovieBatch;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMoviePage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $page;

    private $batchId;

    public function __construct(int $page, int $batchId)
    {
        $this->page = $page;
        $this->batchId = $batchId;
    }

    public function withBatchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function handle()
    {
        $movieBatch = MovieBatch::findOrFail($this->batchId);

        try {
            $movies = $this->fetchMoviesPage($this->page);

            if ($movies && ! empty($movies['results'])) {
                $processedCount = $this->processMovies($movies['results'], $movieBatch);

                $movieBatch->increment('movies_processed', $processedCount);
            }
        } catch (Throwable $e) {
            Log::error('Failed to process movie page', [
                'page' => $this->page,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function fetchMoviesPage(int $page): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.tmdb.api_token'),
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/movie/popular', [
            'page' => $page,
            'language' => 'en-US',
        ]);

        if (! $response->successful()) {
            Log::error('TMDB API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    private function processMovies(array $movies, MovieBatch $batch): int
    {
        $processedCount = 0;
        $fetchTime = Carbon::now();

        foreach ($movies as $movieData) {
            if ($this->processMovie($movieData, $batch, $fetchTime)) {
                $processedCount++;
            }
        }

        return $processedCount;
    }

    private function processMovie(array $movieData, MovieBatch $batch, Carbon $fetchTime): bool
    {
        if (empty($movieData['id'])) {
            Log::warning('Missing external_id for movie', ['data' => $movieData]);

            return false;
        }

        $movieAttributes = [
            'title' => $movieData['title'],
            'description' => $movieData['overview'] ?? null,
            'external_id' => (string) $movieData['id'],
            'year' => isset($movieData['release_date']) ? Carbon::parse($movieData['release_date'])->year : null,
            'batch_id' => $batch->id,
        ];

        $movie = Movie::updateOrCreate(
            ['external_id' => (string) $movieData['id']],
            $movieAttributes
        );

        Log::info('Movie processed', ['movie_id' => $movie->id, 'batch_id' => $batch->id]);
        $this->updateMovieLogs($movie, $movieData, $fetchTime, $batch);

        return true;
    }

    private function updateMovieLogs(Movie $movie, array $movieData, Carbon $fetchTime, MovieBatch $batch): void
    {
        $logEntry = [
            'fetched_at' => $fetchTime->toIso8601String(),
            'completed_at' => Carbon::now()->toIso8601String(),
            'status' => 'success',
            'api_data' => $movieData,
            'batch_id' => $batch->id,
            'page' => $this->page,
        ];

        $logs = $movie->fetch_logs ?? [];
        array_push($logs, $logEntry);

        if (count($logs) > 10) {
            $logs = array_slice($logs, -10);
        }

        $movie->fetch_logs = $logs;
        $movie->save();
    }
}
