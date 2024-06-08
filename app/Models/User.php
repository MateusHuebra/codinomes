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
            ->whereIn('games.status', ['playing', 'creating'])
            ->withPivot('team', 'role')
            ->as('player')
            ->first();
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
