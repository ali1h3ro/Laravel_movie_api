<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class MovieBatch extends Model
{
    // Fillable attributes that can be mass assigned
    protected $fillable = [
        // Batch start and end times
        'started_at',
        'completed_at',

        // Batch status tracking
        'status', // e.g., 'pending', 'processing', 'completed', 'failed'

        // Pagination and tracking information
        'total_pages',      // Total number of pages in this batch
        'pages_processed',  // Number of pages successfully processed
        'current_page',     // Current page being processed

        // Movie-related statistics
        'total_movies',     // Total movies in this batch
        'movies_processed', // Number of movies successfully processed

        // Error handling
        'error_message',    // Any error that occurred during batch processing

        // API-related metadata
        'source_api',       // API source (e.g., 'TMDB', 'IMDB')
        'api_endpoint',     // Specific endpoint used
        'fetch_parameters'  // JSON of parameters used for fetching
    ];

    // Cast certain attributes to specific types
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'fetch_parameters' => 'array',
        'total_pages' => 'integer',
        'pages_processed' => 'integer',
        'total_movies' => 'integer',
        'movies_processed' => 'integer'
    ];

    // Relationship with Movies
    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class, 'batch_id');
    }

    // Scope for filtering active batches
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    // Scope for completed batches
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Scope for failed batches
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Check if batch is complete
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    // Check if batch failed
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Calculate batch processing time
    public function processingTime(): ?float
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->completed_at);
    }

    // Calculate success rate
    public function successRate(): float
    {
        if ($this->total_movies == 0) {
            return 0;
        }
        return ($this->movies_processed / $this->total_movies) * 100;
    }

    // Get movies with processing errors
    public function getFailedMovies()
    {
        return $this->movies()->where('status', 'failed')->get();
    }

    // Static method to create a new batch
    public static function createBatch(array $data): self
    {
        return self::create(array_merge([
            'started_at' => now(),
            'status' => 'pending',
            'pages_processed' => 0,
            'movies_processed' => 0
        ], $data));
    }

    // Method to mark batch as failed
    public function markAsFailed(string $errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    // Method to mark batch as completed
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'pages_processed' => $this->total_pages,
            'movies_processed' => $this->total_movies
        ]);
    }
}
