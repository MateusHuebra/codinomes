<?php

namespace App\Models;

use App\Services\AppString;
use App\Services\Telegram\BotApi;
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
        'sixsixsix',
        'possessed',
        'hurry',
        'good_start',
        'day_is_night',
        'impostor',
        'rainbow',
        'graduated',
        'addicted',
        'proplayer',
        'pride',
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
        $usersForRainbow = new Collection();
        $usersForGratuated = new Collection();
        $usersForAddicted = new Collection();
        $usersForProPlayer = new Collection();
        $usersForPride = new Collection();

        foreach($users as $user) {
            $winColorStats = $user->colorStats()
                ->where(function ($query) {
                    $query->where('wins_as_master', '!=', 0)
                        ->orWhere('wins_as_agent', '!=', 0);
                })
                ->get();
            
            if(self::doesUserHaveAllJuneColors($user->colorStats)) {
                $usersForPride->add($user);
            }

            if(self::doesUserHaveAllColors($winColorStats)) {
                $usersForRainbow->add($user);
                $usersForGratuated->add($user);
                continue;
            }
            
            if(self::doesUserHaveAllColors($user->colorStats)) {
                $usersForRainbow->add($user);
            }
        }

        foreach($users as $user) {
            $stats = $user->stats;
            $totalWins = $stats->wins_as_master + $stats->wins_as_agent;
            $totalGames = $stats->games_as_master + $stats->games_as_agent;

            if($totalWins >= 100) {
                $usersForAddicted->add($user);
                $usersForProPlayer->add($user);
                continue;
            }
            
            if($totalGames >= 100) {
                $usersForAddicted->add($user);
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
        if($usersForPride->count() != 0) {
            self::add($usersForPride, 'pride', $bot, $chatId);
        }

        if($users->contains('id', env('TG_MY_ID'))) {
            self::add($users, 'kian_knows', $bot, $chatId);
        }
        if(in_array($chatId, explode(',', env('TG_OFICIAL_GROUPS_IDS')))) {
            self::add($users, 'making_friends', $bot, $chatId);
        }
    }

    public static function checkBlackCard(Game $game, int $cardsLeft, $player, BotApi $bot) {
        if(!in_array($game->mode, [Game::MINESWEEPER, Game::EIGHTBALL, Game::FAST])) {
            if($cardsLeft == 1) {
                $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
                self::add($agents, 'day_is_night', $bot, $game->chat_id);
                
            } else if ($cardsLeft == $game->cards->where('team', $player->team)->count()) {
                $agents = $game->users()->fromTeamRole($player->team, 'agent')->get();
                self::add($agents, 'good_start', $bot, $game->chat_id);
            }
        }
    }

    private static function doesUserHaveAllColors(Collection $colorStats) {
        $result = true;
        foreach(Game::COLORS as $color => $emoji) {
            if(in_array($color, ['white', 'black', 'rbow', 'cotton', 'flower', 'dna', 'moon'])) {
                continue;
            }
            
            if(!$colorStats->contains('color', $color)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    private static function doesUserHaveAllJuneColors(Collection $colorStats) {
        $result = true;
        foreach(['rbow', 'cotton', 'flower', 'dna', 'moon'] as $color) {
            if(!$colorStats->contains('color', $color)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

}
