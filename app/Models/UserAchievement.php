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
        //'possessed',
        //'hurry',
        'good_start',
        'day_is_night',
        'impostor',
        'rainbow',
        'graduated',
        'addicted',
        'proplayer',
        'egghunt',
        'pride',
        'independence',
        'scary',
        'itstime',
        'kian_knows',
        'making_friends',
        'every_shot',
        'thin_ice',
        'lovebirds',
        'egg_hunt'
    ];

    public static function add(Collection $users, string $achievement, BotApi $bot, int $chatId = null) {
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

        if($chatId) {
            $bot->sendMessage($chatId, $text, 'MarkdownV2');
        } else {
            foreach ($users as $user) {
                $bot->sendMessage($user->id, $text, 'MarkdownV2');
            }
        }
    }

    public static function testEndGame(Collection $users, BotApi $bot, int $chatId) {
        $usersForRainbow = new Collection();
        $usersForGratuated = new Collection();
        $usersForAddicted = new Collection();
        $usersForProPlayer = new Collection();
        $usersForEggHunt = new Collection();
        $usersForPride = new Collection();
        $usersForIndependence = new Collection();
        $usersForScary = new Collection();
        $usersForItsTime = new Collection();

        foreach($users as $user) {
            $winColorStats = $user->colorStats()
                ->where(function ($query) {
                    $query->where('wins_as_master', '!=', 0)
                        ->orWhere('wins_as_agent', '!=', 0);
                })
                ->get();
            
            if(self::doesUserHaveAllAprilColors($user->colorStats)) {
                $usersForEggHunt->add($user);
            }
            if(self::doesUserHaveAllJuneColors($user->colorStats)) {
                $usersForPride->add($user);
            }
            if(self::doesUserHaveAllSeptemberColors($user->colorStats)) {
                $usersForIndependence->add($user);
            }
            if(self::doesUserHaveAllOctoberColors($user->colorStats)) {
                $usersForScary->add($user);
            }
            if(self::doesUserHaveAllDecemberColors($user->colorStats)) {
                $usersForItsTime->add($user);
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
        if($usersForEggHunt->count() != 0) {
            self::add($usersForEggHunt, 'egghunt', $bot, $chatId);
        }
        if($usersForPride->count() != 0) {
            self::add($usersForPride, 'pride', $bot, $chatId);
        }
        if($usersForIndependence->count() != 0) {
            self::add($usersForIndependence, 'independence', $bot, $chatId);
        }
        if($usersForScary->count() != 0) {
            self::add($usersForScary, 'scary', $bot, $chatId);
        }
        if($usersForItsTime->count() != 0) {
            self::add($usersForItsTime, 'itstime', $bot, $chatId);
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
        $colors = TeamColor::where('is_free', true)->get()->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllAprilColors(Collection $colorStats) {
        $colors = Event::where('shortname', 'easter')->first()->teamColors->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllJuneColors(Collection $colorStats) {
        $colors = Event::where('shortname', 'pride')->first()->teamColors->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllSeptemberColors(Collection $colorStats) {
        $colors = Event::where('shortname', 'independence')->first()->teamColors->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllOctoberColors(Collection $colorStats) {
        $colors = Event::where('shortname', 'halloween')->first()->teamColors->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllDecemberColors(Collection $colorStats) {
        $colors = Event::where('shortname', 'christmas')->first()->teamColors->pluck('shortname')->toArray();
        return self::doesUserHaveAllInArrayColors($colorStats, $colors);
    }

    private static function doesUserHaveAllInArrayColors(Collection $colorStats, Array $colors) {
        $result = true;
        foreach($colors as $color) {
            if(!$colorStats->contains('color', $color)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

}
