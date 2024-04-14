<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\Pack;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use Exception;

class Start implements Action {

    public function run($update, BotApi $bot) : Void {
        $updateId = $update->getId();
        $messageId = $update->getMessage()->getMessageId();
        $chatId = $update->getMessage()->getChat()->getId();
        $game = Game::where('chat_id', $chatId)->first();

        if(!$game || $game->status != 'creating') {
            $bot->deleteMessage($chatId, $messageId);
            return;
        }

        $masterA = $game->users()->fromTeamRole('a', 'master');
        $agentsA = $game->users()->fromTeamRole('a', 'agent');
        $masterB = $game->users()->fromTeamRole('b', 'master');
        $agentsB = $game->users()->fromTeamRole('b', 'agent');

        if($masterA->count()==0 || $agentsA->count()==0 || $masterB->count()==0 || $agentsB->count()==0) {
            $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.no_required_players');
            return;
        }

        $teams = ['a', 'b'];
        $firstTeam = $teams[rand(0, 1)];
        $game->updateStatus('master_'.$firstTeam);

        $pack = Pack::find(1);
        $cards = $pack->cards;
        $randomizedCards = $cards->random(25);
        $shuffledCards = $randomizedCards->toArray();
        shuffle($shuffledCards);

        $teams = [
            'a' => 8+($firstTeam=='a'?1:0),
            'b' => 8+($firstTeam=='b'?1:0),
            'x' => 1
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

        foreach ($shuffledCards as $key => $card) {
            $gameCard = new GameCard;
            $gameCard->game_id = $game->id;
            $gameCard->id = $key;
            $gameCard->text = $card['text'];
            $gameCard->team = $card['team']??'w';
            $gameCard->revealed = false;
            $gameCard->save();
        }

        try {
            $bot->deleteMessage($chatId, $messageId);
            $bot->answerCallbackQuery($updateId, AppString::get('settings.loading'));
        } catch(Exception $e) {}
        Table::send($game, $bot);
    }

}