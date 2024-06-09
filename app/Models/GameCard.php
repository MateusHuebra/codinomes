<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameCard extends Model
{
    use HasFactory;
    public $timestamps = false;
    private static $cardsCounts = [];

    public static function set(Game $game, string $firstTeam) : Bool {
        if($game->chat->packs()->count() == 0) {
            $game->chat->packs()->attach(1);
        }
        $cards = $game->chat->packs->getCards();

        switch ($game->mode) {
            case 'fast':
                self::$cardsCounts = [
                    'base' => 4,
                    'black' => 2,
                    'max' => 14
                ];
                break;
            
            default:
                self::$cardsCounts = [
                    'base' => 8,
                    'black' => 1,
                    'max' => 25
                ];
                break;
        }

        if($cards->count() < self::$cardsCounts['max']) {
            return false;
        }

        $randomizedCards = $cards->random(self::$cardsCounts['max']);
        $shuffledCards = $randomizedCards->toArray();
        shuffle($shuffledCards);
        $shuffledCards = self::getColoredCards($shuffledCards, $firstTeam);

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

    private static function getColoredCards(array $shuffledCards, string $firstTeam) {
        $teams = [
            'a' => self::$cardsCounts['base'] + ($firstTeam=='a'?1:0),
            'b' => self::$cardsCounts['base'] + ($firstTeam=='b'?1:0),
            'x' => self::$cardsCounts['black']
        ];
        
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
        while($teams['x']>0) {
            $randomIndex = rand(0, self::$cardsCounts['max']-1);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'x';
                $teams['x']--;
            }
        }

        return $shuffledCards;
    }
}
