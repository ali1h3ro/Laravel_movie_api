<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'director' => $this->director,
            'year' => $this->year,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')), // Include reviews if loaded
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
