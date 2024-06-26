<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pack extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function chats()
    {
        return $this->belongsToMany(Chat::class);
    }

    public function newCollection(array $models = [])
    {
        return new \App\Collections\Pack($models);
    }
}
