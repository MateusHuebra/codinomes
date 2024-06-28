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
        if($game->mode == 'coop') {
            $cards = Card::where('pack_id', 1)->get();

        } else {
            if($game->chat->packs()->count() == 0) {
                $game->chat->packs()->attach(1);
            }
            $cards = $game->chat->packs->getCards();
        }
        
        self::setCardsCountsByMode($game->mode);

        if($cards->count() < self::$cardsCounts['max']) {
            return false;
        } else if($game->mode == 'sp_crazy' && $cards->count() < self::$cardsCounts['max']*2) {
            return false;
        }

        $randomizedCards = $cards->random(self::$cardsCounts['max']);
        $shuffledCards = $randomizedCards->toArray();
        shuffle($shuffledCards);
        $shuffledCards = self::getColoredCards($shuffledCards, $firstTeam, $game->mode == 'triple');

        foreach ($shuffledCards as $key => $card) {
            $gameCard = new GameCard;
            $gameCard->game_id = $game->id;
            $gameCard->position = $key;
            $gameCard->text = $card['text'];
            $gameCard->team = $card['team']??'w';
            $gameCard->revealed = false;
            if(env('APP_ENV')=='local') {
                $gameCard->revealed = rand(0,1)?true:false;
            }
            $gameCard->save();
        }
        return true;
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

        var_dump([
            'aTeamCount' => $aTeamCount,
            'bTeamCount' => $bTeamCount,
            'whiteCount' => $whiteCount,
            'blackCount' => $blackCount
        ]);

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
            case 'fast':
                self::$cardsCounts = [
                    'base' => 4,
                    'black' => 1,
                    'max' => 12
                ];
                break;

            case 'mineswp':
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 7,
                    'max' => 24
                ];
                break;

            case '8ball':
                self::$cardsCounts = [
                    'base' => 7,
                    'black' => 1,
                    'max' => 24
                ];
                break;

            case 'triple':
                self::$cardsCounts = [
                    'base' => 7,
                    'black' => 0,
                    'max' => 24
                ];
                break;
            
            default:
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 1,
                    'max' => 24
                ];
                break;
        }
    }

    private static function getColoredCards(array $shuffledCards, string $firstTeam, bool $triple = false) {
        $teams = [
            'a' => self::$cardsCounts['base'] + ($firstTeam=='a'?1:0),
            'b' => self::$cardsCounts['base'] + ($firstTeam=='b'?1:0),
            'x' => self::$cardsCounts['black']
        ];
        if($triple) {
            $teams+= [
                'c' => self::$cardsCounts['base'] + ($firstTeam=='c'?1:0),
            ];
            $teams = self::removeOneCardForLastTeam($teams, $firstTeam);
        }
        
        while($teams['a']>0) {
            $randomIndex = rand(0, self::$cardsCounts['max']-1);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'a';
                $teams['a']--;
            }
        }
        while($teams['b']>0) {
            $randomIndex = rand(0, self::$cardsCounts['max']-1);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'b';
                $teams['b']--;
            }
        }
        while($triple && $teams['c']>0) {
            $randomIndex = rand(0, self::$cardsCounts['max']-1);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'c';
                $teams['c']--;
            }
        }
        while($teams['x']>0) {
            $randomIndex = rand(0, self::$cardsCounts['max']-1);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'x';
                $teams['x']--;
            }
        }

        return $shuffledCards;
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

}
