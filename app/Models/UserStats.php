<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStats extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'user_id'
    ];

    public static function addAttempt(Game $game, string $team, string $type) {
        $streak = $type == 'ally' ? $game->countLastStreak() : null;

        $master = $game->users()->fromTeamRole($team, 'master')->first();
        self::setStatsForUsers([$master], 'master', $type, $streak);

        $agents = $game->users()->fromTeamRole($team, 'agent')->get();
        self::setStatsForUsers($agents, 'agent', $type, $streak);
    }

    private static function setStatsForUser(array $users, string $role, string $type, int $streak = null) {
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
