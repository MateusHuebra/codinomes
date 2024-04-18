<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\User as TGUser;
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'username',
        'name',
        'language',
        'status'
    ];

    public function leaveGame() {
        $this->game_id = null;
        $this->team = null;
        $this->role = null;
        $this->save();
    }

    public function scopeFromTeamRole(Builder $query, string $team, string $role): void
    {
        $query->where('team', $team)->where('role', $role);
    }

    public function newCollection(array $models = [])
    {
        return new \App\Collections\User($models);
    }

    public function getEnemyTeam() : String {
        if($this->team == 'a') {
            return 'b';
        }
        return 'a';
    }

    static function createFromTGModel(TGUser $tgUser) : User {
        return self::create([
            'id' => $tgUser->getId(),
            'username' => $tgUser->getUsername(),
            'name' => substr($tgUser->getFirstName(), 0, 32),
            'language' => $tgUser->getLanguageCode(),
            'status' => 'actived'
        ]);
    }
}
