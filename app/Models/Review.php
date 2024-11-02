<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;
    protected $fillable = [
        'movie_id',
        'author',
        'comment',
        'rating'
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
