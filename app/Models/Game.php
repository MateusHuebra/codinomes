<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;
    
    const A_EMOJI = '🟣';
    const B_EMOJI = '🟠';

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stop() {
        foreach($this->users as $user) {
            $user->leaveGame();
        }
        $this->delete();
    }
}
