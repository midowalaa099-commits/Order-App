<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'is_open' => $this->is_open,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'meals' => MealResource::collection(
                $this->whenLoaded('meals')
            ),
        ];
    }
}
