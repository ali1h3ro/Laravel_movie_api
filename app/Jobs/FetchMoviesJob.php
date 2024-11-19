<?php

namespace App\Jobs;

use App\Models\MovieBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchMoviesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            // First, we'll make an initial API call to get the total pages
            $initialResponse = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.tmdb.api_token'),
                'Accept' => 'application/json',
            ])->get('https://api.themoviedb.org/3/movie/popular', [
                'page' => 1,
                'language' => 'en-US',
            ]);

            if (! $initialResponse->successful()) {
                throw new \Exception('Failed to fetch initial movie data');
            }

            $data = $initialResponse->json();
            $totalPages = min($data['total_pages'] ?? 1, 150); // Limit to 150 pages max

            // Create a new batch record
            $movieBatch = MovieBatch::create([
                'started_at' => now(),
                'status' => 'processing',
                'total_pages' => $totalPages,
                'pages_processed' => 0,
            ]);

            // Dispatch a job for each page
            $jobs = [];
            for ($page = 1; $page <= $totalPages; $page++) {
                $jobs[] = new ProcessMoviePage($page, $movieBatch->id);
            }

            // Use Laravel's batch processing
            Bus::batch($jobs)
                ->allowFailures()
                ->onQueue('movies')
                ->dispatch();

            Log::info('Movie fetch jobs dispatched', [
                'batch_id' => $movieBatch->id,
                'total_pages' => $totalPages,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to initialize movie fetch', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
