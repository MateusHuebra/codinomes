<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    const TEAM = [
        'a' => [
            'emoji' => '🟣',
            'color' => 'purple'
        ],
        'b' => [
            'emoji' => '🟠',
            'color' => 'orange'
        ]
    ];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class);
    }

    public function stop() {
        foreach($this->users as $user) {
            $user->leaveGame();
        }
        foreach($this->cards as $card) {
            $card->delete();
        }
        $this->delete();
    }

    public function updateStatus(string $status) {
        $this->status = $status;
        $this->status_updated_at = date('Y-m-d H-i-s');
        $this->save();
    }

    public function addToHistory(string $line) {
        $this->history = $this->history.PHP_EOL.$line;
        $this->save();
    }

}
