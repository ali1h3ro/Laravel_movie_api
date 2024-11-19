<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'director',
        'year',
        'external_id',  // Make sure external_id is fillable
        'fetch_logs',
        'batch_id' // Add this field to link to the batch model
    ];
    protected $casts = [
        'fetch_logs' => 'array',
        'year' => 'integer'
    ];

    public function batch()
    {
        return $this->belongsTo(MovieBatch::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function scopeSearch(Builder $query, string $search): Builder
    {

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by year.
     */
    public function scopeYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to filter by director.
     */
    public function scopeDirector(Builder $query, string $director): Builder
    {
        return $query->where('director', 'LIKE', "%{$director}%");
    }

    /**
     * Scope a query to apply sorting.
     */
    public function scopeSort(Builder $query, string $sortBy = 'created_at', string $order = 'desc'): Builder
    {
        return $query->orderBy($sortBy, $order);
    }
}


