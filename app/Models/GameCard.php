<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameCard extends Model
{
    use HasFactory;
    public $timestamps = false;

    public static function set(Game $game, string $firstTeam) : Bool {
        if($game->chat->packs()->count() == 0) {
            $game->chat->packs()->attach(1);
        }
        $cards = $game->chat->packs->getCards();

        if($cards->count() < 25) {
            return false;
        }

        $randomizedCards = $cards->random(25);
        $shuffledCards = $randomizedCards->toArray();
        shuffle($shuffledCards);
        $shuffledCards = self::getColoredCards($game->mode, $shuffledCards, $firstTeam);

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

    private static function getColoredCards(string $gameMode, array $shuffledCards, string $firstTeam) {
        switch ($gameMode) {
            case 'fast':
                $baseCardsCount = 4;
                $blackCardsCount = 3;
                break;
            
            default:
                $baseCardsCount = 8;
                $blackCardsCount = 1;
                break;
        }

        $teams = [
            'a' => $baseCardsCount + ($firstTeam=='a'?1:0),
            'b' => $baseCardsCount + ($firstTeam=='b'?1:0),
            'x' => $blackCardsCount
        ];
        
        while($teams['a']>0) {
            $randomIndex = rand(0, 24);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'a';
                $teams['a']--;
            }
        }
        while($teams['b']>0) {
            $randomIndex = rand(0, 24);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'b';
                $teams['b']--;
            }
        }
        while($teams['x']>0) {
            $randomIndex = rand(0, 24);
            if(!isset($shuffledCards[$randomIndex]['team'])) {
                $shuffledCards[$randomIndex]['team'] = 'x';
                $teams['x']--;
            }
        }

        return $shuffledCards;
    }
}
