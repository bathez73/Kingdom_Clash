<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Card extends Model
{
    protected $fillable = [
        'name',
        'type',
        'base_damage',
        'base_hp',
        'elixir_cost',
        'icon',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['level', 'copies_count'])
            ->withTimestamps();
    }
}
