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

    public function getNameAttribute($value)
    {
        // Remove emojis using regex
        $cleanName = preg_replace('/[\x{1F600}-\x{1F64F}'
            . '\x{1F300}-\x{1F5FF}'
            . '\x{1F680}-\x{1F6FF}'
            . '\x{1F1E0}-\x{1F1FF}'
            . '\x{2600}-\x{26FF}'
            . '\x{2700}-\x{27BF}'
            . '\x{FE00}-\x{FE0F}'
            . '\x{1F900}-\x{1F9FF}'
            . '\x{1F018}-\x{1F270}'
            . '\x{238C}-\x{2454}]++/u', '', $value);

        $cleanName = trim($cleanName);

        if ($cleanName === '') {
            $cleanName = 'Sem Nome';
        }

        $activeBadge = UserBadge::EMOJIS[$this->active_badge];
        if ($activeBadge) {
            $cleanName .= ' ' . $activeBadge;
        }

        return $cleanName;
    }

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

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
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

    public function getEnemyTeam(): String
    {
        if ($this->currentGame()->player->team == 'a') {
            return 'b';
        }
        return 'a';
    }

    public function getNextTeam(): String
    {
        $game = $this->currentGame();
        $isTriple = $game->mode == Game::TRIPLE;
        if ($isTriple) {
            switch ($game->player->team) {
                case 'a':
                    return 'b';
                case 'b':
                    return 'c';
                default:
                    return 'a';
            }
        } else {
            return $this->getEnemyTeam();
        }
    }

    public function getEnemyTeams(bool $triple = false): array
    {
        $array = [];
        if ($this->currentGame()->player->team !== 'a') {
            $array[] = 'a';
        }
        if ($this->currentGame()->player->team !== 'b') {
            $array[] = 'b';
        }
        if ($triple && $this->currentGame()->player->team !== 'c') {
            $array[] = 'c';
        }
        return $array;
    }

    public function isVip(): bool
    {
        if ($this->id == env('TG_MY_ID')) {
            return true;
        }
        return false;
    }

    public function hasBadge(string $badge): bool
    {
        return $this->badges()->where('badge_shortname', $badge)->exists();
    }

    static function createFromTGModel(TGUser $tgUser): User
    {
        return self::create([
            'id' => $tgUser->getId(),
            'username' => $tgUser->getUsername(),
            'name' => mb_substr($tgUser->getFirstName(), 0, 32, 'UTF-8'),
            'language' => $tgUser->getLanguageCode(),
            'status' => 'actived'
        ]);
    }
}
