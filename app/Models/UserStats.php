<?php

namespace App\Models;

use App\Services\Telegram\BotApi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;


class UserStats extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'user_id'
    ];

    public static function addAttempt(Game $game, string $team, string $type, BotApi $bot) {
        $streak = $type == 'ally' ? $game->countLastStreak() : 0;

        $master = $game->users()->fromTeamRole($team, 'master')->get();
        self::setStatsForUsers($master, 'master', $type, $streak);

        $agents = $game->users()->fromTeamRole($team, 'agent')->get();
        self::setStatsForUsers($agents, 'agent', $type, $streak);

        if($streak == 6) {
            UserAchievement::add($agents, 'possessed', $bot, $game->chat_id);
        }
    }

    private static function setStatsForUsers(Collection $users, string $role, string $type, int $streak = 0) {
        if($role == 'master') {
            $statsColumn = 'hinted_to_'.$type;
            $streakColumn = 'hinted_to_ally_streak';
        } else {
            $statsColumn = 'attempts_on_'.$type;
            $streakColumn = 'attempts_on_ally_streak';
        }

        foreach ($users as $user) {
            $stats = self::firstOrNew([
                'user_id' => $user->id
            ]);
            $stats->{$streakColumn} = ($type != 'ally' || $stats->{$streakColumn} > $streak) ? $stats->{$streakColumn} : $streak;
            $stats->{$statsColumn}+= 1;
            $stats->save();
        }
    }

}
