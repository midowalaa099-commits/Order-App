<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
        use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean'
        ];
    }
    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
