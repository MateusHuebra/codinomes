<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\User as TGUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function stats(): HasOne
    {
        return $this->hasOne(UserStats::class);
    }

    public function colorStats(): HasMany
    {
        return $this->hasMany(UserColorStats::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
    
    public function games()
    {
        return $this->belongsToMany(Game::class)->withPivot('team', 'role')->as('player');
    }

    public function currentGame()
    {
        return $this->belongsToMany(Game::class)
            ->whereIn('games.status', ['playing', 'lobby', 'creating'])
            ->withPivot('team', 'role')
            ->as('player')
            ->first();
    }

    public function chatsToNotify()
    {
        return $this->belongsToMany(Chat::class, 'notifiable_chat_users')->withPivot('message_id');
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
        if($this->currentGame()->player->team == 'a') {
            return 'b';
        }
        return 'a';
    }

    public function getNextTeam() : String {
        switch ($this->currentGame()->player->team) {
            case 'a':
                return 'b';
            case 'b':
                return 'c';
            default:
                return 'a';
        }
    }

    public function getEnemyTeams(bool $triple = false) {
        $array = [];
        if($this->currentGame()->player->team !== 'a') {
            $array[] = 'a';
        }
        if($this->currentGame()->player->team !== 'b') {
            $array[] = 'b';
        }
        if($triple && $this->currentGame()->player->team !== 'c') {
            $array[] = 'c';
        }
        return $array;
    }

    static function createFromTGModel(TGUser $tgUser) : User {
        return self::create([
            'id' => $tgUser->getId(),
            'username' => $tgUser->getUsername(),
            'name' => mb_substr($tgUser->getFirstName(), 0, 32, 'UTF-8'),
            'language' => $tgUser->getLanguageCode(),
            'status' => 'actived'
        ]);
    }
}
