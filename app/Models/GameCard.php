<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameCard extends Model
{
    use HasFactory;
    public $timestamps = false;
    private static $cardsCounts = [];

    public static function set(Game $game, string $firstTeam) : Bool {
        $cardsToBeAdded = self::getCardsToBeAdded($game);
        self::setCardsCountsByMode($game->mode);

        if($cardsToBeAdded->count() < self::$cardsCounts['max']) {
            return false;
        } else if($game->mode == Game::SUPER_CRAZY && $cardsToBeAdded->count() < self::$cardsCounts['max']*2) {
            return false;
        }

        $cardsToBeAdded = $cardsToBeAdded->random(self::$cardsCounts['max']);
        $cardsToBeAdded = $cardsToBeAdded->shuffle();
        $gameCards = new Collection();
        
        $position = 0;
        foreach ($cardsToBeAdded as $card) {
            $gameCard = new GameCard;
            $gameCard->game_id = $game->id;
            $gameCard->position = $position;
            $gameCard->text = $card->text;
            $gameCard->revealed = false;// TODO env('APP_ENV')=='local' ? (bool) rand(0,1) : false;
            $gameCard->coop_revealed = $game->mode == Game::COOP ? false : null;
            $gameCard->coop_team = $game->mode == Game::COOP ? 'w' : null;
            $gameCards->add($gameCard);
            $position++;
        }

        $teamCount = self::getTeamCount($firstTeam, $game->mode);

        $gameCards = self::setColors($gameCards, $teamCount['a'], 'a');
        $gameCards = self::setColors($gameCards, $teamCount['b'], 'b');
        $gameCards = self::setColors($gameCards, $teamCount['c'], 'c');
        $gameCards = self::setColors($gameCards, $teamCount['w'], 'w');
        $gameCards = self::setColors($gameCards, $teamCount['x'], 'x');

        if($game->mode == Game::COOP) {
            self::setCoopColors($game->id);
        }

        return true;
    }

    private static function getCardsToBeAdded(Game $game) {
        if($game->mode == Game::COOP) {
            $coopPacksChatId = $game->creator->coop_packs_chat_id;
            if($coopPacksChatId) {
                $chat = Chat::find($coopPacksChatId);
            }
            return self::getBasePacksByLanguage($game->creator->language);
        } else {
            $chat = $game->chat;
        }

        if($chat->packs()->count() == 0) {
            return false;
        }
        return $chat->packs->getCards();
    }

    private static function getBasePacksByLanguage(string $language) {
        switch ($language) {
            case 'pt-br':
                $packId = 1;
                break;
            
            default:
                $packId = 5;
                break;
        }
        return Card::where('pack_id', $packId)->get();
    }

    public static function randomizeUnrevealedCardsWords(Game $game) {
        $cards = $game->cards()->get();
        $texts = $cards->pluck('text')->toArray();
        $cards = $cards->where('revealed', false);

        $cardsToBeAdded = $game->chat->packs->getCards()
            ->reject(function ($item) use ($texts) {
                return in_array($item->text, $texts);
            });
        $cardsToBeAdded = $cardsToBeAdded->random($cards->count());
        $cardsToBeAdded = $cardsToBeAdded->shuffle();

        foreach($cardsToBeAdded as $cardToBeAdded) {
            $card = $cards->first();
            $card->text = $cardToBeAdded->text;
            $key = $cards->search($card);
            $cards->forget($key);
            $card->save();
        }
    }

    public static function randomizeUnrevealedCardsColors(Game $game) {
        $cards = $game->cards()
            ->where('revealed', false)
            ->get();
        
        $aTeamCount = $cards->where('team', 'a')->count();
        $bTeamCount = $cards->where('team', 'b')->count();
        $whiteCount = $cards->where('team', 'w')->count();
        $blackCount = $cards->where('team', 'x')->count();

        $cards = self::setColors($cards, $aTeamCount, 'a');
        $cards = self::setColors($cards, $bTeamCount, 'b');
        $cards = self::setColors($cards, $whiteCount, 'w');
        $cards = self::setColors($cards, $blackCount, 'x');

    }

    private static function setColors(Collection $cards, int $quantity, string $team) {
        while($quantity > 0) {
            $card = $cards->random();
            $key = $cards->search($card);
            $cards->forget($key);
            $card->team = $team;
            $card->save();
            $quantity--;
        }
        return $cards;
    }

    private static function setCardsCountsByMode(string $gameMode) {
        switch ($gameMode) {
            case Game::FAST:
                self::$cardsCounts = [
                    'base' => 4,
                    'black' => 1,
                    'white' => 2,
                    'max' => 12
                ];
                break;

            case Game::MINESWEEPER:
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 7,
                    'white' => 0,
                    'max' => 24
                ];
                break;

            case Game::EIGHTBALL:
                self::$cardsCounts = [
                    'base' => 7,
                    'black' => 1,
                    'white' => 8,
                    'max' => 24
                ];
                break;

            case Game::TRIPLE:
                self::$cardsCounts = [
                    'base' => 7,
                    'black' => 0,
                    'white' => 3,
                    'max' => 24
                ];
                break;

            case Game::COOP:
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 3,
                    'white' => 12,
                    'max' => 24
                ];
                break;
            
            default:
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 1,
                    'white' => 6,
                    'max' => 24
                ];
                break;
        }
    }

    private static function getTeamCount(string $firstTeam, string $gameMode) {
        $teams = [
            'a' => self::$cardsCounts['base'] + ($firstTeam=='a'?1:0),
            'b' => $gameMode == Game::COOP
                   ? 0
                   : self::$cardsCounts['base'] + ($firstTeam=='b'?1:0),
            'c' => $gameMode == Game::TRIPLE
                   ? self::$cardsCounts['base'] + ($firstTeam=='c'?1:0)
                   : 0,
            'w' => self::$cardsCounts['white'],
            'x' => self::$cardsCounts['black']
        ];
        if($gameMode == Game::TRIPLE) {
            $teams = self::removeOneCardForLastTeam($teams, $firstTeam);
        }
        
        return $teams;
    }

    private static function removeOneCardForLastTeam(array $teams, string $firstTeam) {
        switch ($firstTeam) {
            case 'a':
                $teams['c']--;
                break;
            case 'b':
                $teams['a']--;
                break;
            case 'c':
                $teams['b']--;
                break;
        }
        return $teams;
    }

    private static function setCoopColors(int $gameId) {
        $cards = self::where('game_id', $gameId)->get();
        $blackCards = $cards->where('team', 'x');
        $whiteCards = $cards->where('team', 'w');
        $coloredCards = $cards->where('team', 'a');

        self::setCoopColorForCollection($blackCards, 1);
        self::setCoopColorForCollection($whiteCards, 5);
        self::setCoopColorForCollection($coloredCards, 3);
    }

    private static function setCoopColorForCollection(Collection $cards, int $coloredCards) {
        $i = 0;
        foreach($cards as $card) {
            $card = $cards->random();
            $card->coop_team = self::getColorOrBlackByLimit($i, $coloredCards);
            $key = $cards->search($card);
            $cards->forget($key);
            $card->save();
            $i++;
            if($i == $coloredCards+1) {
                break;
            }
        }
    }

    private static function getColorOrBlackByLimit(int $number, int $limit) {
        if($number < $limit) {
            return 'a';

        } else {
            return 'x';
        }
    }

}
