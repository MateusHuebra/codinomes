<?php

namespace App\Models;

use App\Services\AppString;
use App\Services\Telegram\BotApi;
use Illuminate\Support\Collection as CollectionSupport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'achievement_id'
    ];


    const AVAILABLE = [
        'seven_one',
        'possessed',
        'good_start',
        'day_is_night',
        'impostor',
        'rainbow',
        'graduated',
        'addicted',
        'proplayer',
        'kian_knows',
        'making_friends'
    ];

    public static function add(Collection $users, string $achievement, BotApi $bot, int $chatId) {
        $usersNames = [];

        foreach($users as $user) {
            $alreadyAchieved = self::where('user_id', $user->id)
                ->where('achievement_id', $achievement)
                ->exists();
            if($alreadyAchieved) {
                continue;
            }

            self::create([
                'user_id' => $user->id,
                'achievement_id' => $achievement
            ]);

            $usersNames[] = AppString::get('game.mention', [
                'name' => $user->name,
                'id' => $user->id
            ], null, true);
        }

        if(count($usersNames) == 0) {
            return;
        }

        $usersList = implode(', ', $usersNames);
        $usersList = AppString::replaceLastCommaByAnd($usersList);
        $text = AppString::get('achievements.new', [
            'title' => AppString::get('achievements.'.$achievement),
            'users' => $usersList
        ]);

        $bot->sendMessage($chatId, $text, 'MarkdownV2');
    }

    public static function testEndGame(Collection $users, BotApi $bot, int $chatId) {
        $usersForRainbow = collect();
        $usersForGratuated = collect();
        $usersForAddicted = collect();
        $usersForProPlayer = collect();

        foreach($users as $user) {
            $winColorStats = $user->colorStats()
                ->where('wins_as_master', '!=', 0)
                ->orWhere('wins_as_agent', '!=', 0)
                ->get();
            
            if(self::doesUserHaveAllColors($winColorStats)) {
                $usersForRainbow->push($user);
                $usersForGratuated->push($user);
                continue;
            }
            
            if(self::doesUserHaveAllColors($user->colorStats)) {
                $usersForRainbow->push($user);
            }
        }

        foreach($users as $user) {
            $stats = $user->stats;
            $totalWins = $stats->wins_as_master + $stats->wins_as_agent;
            $totalGames = $stats->games_as_master + $stats->games_as_agent;

            if($totalWins == 100) {
                $usersForAddicted->push($user);
                $usersForProPlayer->push($user);
                continue;
            }
            
            if($totalGames == 100) {
                $usersForAddicted->push($user);
            }
        }

        if($usersForRainbow->count() != 0) {
            self::add($usersForRainbow, 'rainbow', $bot, $chatId);
        }
        if($usersForGratuated->count() != 0) {
            self::add($usersForGratuated, 'graduated', $bot, $chatId);
        }
        if($usersForAddicted->count() != 0) {
            self::add($usersForAddicted, 'addicted', $bot, $chatId);
        }
        if($usersForProPlayer->count() != 0) {
            self::add($usersForProPlayer, 'proplayer', $bot, $chatId);
        }

        if($users->contains('id', env('TG_MY_ID'))) {
            self::add($users, 'kian_knows', $bot, $chatId);
        }
        if(in_array($chatId, explode(',', env('TG_OFICIAL_GROUPS_IDS')))) {
            self::add($users, 'making_friends', $bot, $chatId);
        }
    }

    private static function doesUserHaveAllColors(Collection $colorStats) {
        $result = true;
        foreach(Game::COLORS as $color => $emoji) {
            if(in_array($color, ['white', 'black'])) {
                continue;
            }
            
            if(!$colorStats->contains($color)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

}
