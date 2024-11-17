<?php

namespace Database\Factories;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'movie_id' => Movie::factory(),
        'author' => $this->faker->name,
        'comment' => $this->faker->sentence,
        'rating' => $this->faker->numberBetween(1, 5),
        ];
    }
}
