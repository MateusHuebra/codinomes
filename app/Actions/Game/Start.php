<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use TelegramBot\Api\BotApi;
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

        $user = User::find($update->getFrom()->getId());
        if(!$user || !$game->hasPermission($user, $bot)) {
            $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.admin_only');
            return;
        }

        $masterA = $game->users()->fromTeamRole('a', 'master');
        $agentsA = $game->users()->fromTeamRole('a', 'agent');
        $masterB = $game->users()->fromTeamRole('b', 'master');
        $agentsB = $game->users()->fromTeamRole('b', 'agent');
        if($masterA->count()==0 || $agentsA->count()==0 || $masterB->count()==0 || $agentsB->count()==0) {
            $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.no_required_players');
            if(!env('APP_ENV')=='local') {
                return;
            }
        }

        if($game->chat->packs()->count() == 0) {
            $game->chat->packs()->attach(1);
        }
        $cards = $game->chat->packs->getCards();
        if($cards->count() < 25) {
            $bot->sendAlertOrMessage($update->getId(), $chatId, 'error.no_enough_cards');
            return;
        }

        $firstTeam = rand(0, 1) ? 'a' : 'b';
        $game->updateStatus('master_'.$firstTeam);
        
        $randomizedCards = $cards->random(25);
        $shuffledCards = $randomizedCards->toArray();
        shuffle($shuffledCards);
        $shuffledCards = $this->getColoredCards($shuffledCards, $firstTeam);

        foreach ($shuffledCards as $key => $card) {
            $gameCard = new GameCard;
            $gameCard->game_id = $game->id;
            $gameCard->id = $key;
            $gameCard->text = $card['text'];
            $gameCard->team = $card['team']??'w';
            $gameCard->revealed = false;
            if(env('APP_ENV')=='local') {
                $gameCard->revealed = rand(0,1)?true:false;
            }
            $gameCard->save();
        }

        $text = $update->getMessage()->getText();
        preg_match('/^.*\R.*\R.*\R\R.*\R.*\R.*\R\R/', $text, $matches);
        $text.= AppString::getParsed('game.started');
        try {
            $bot->editMessageText($chatId, $messageId, $text, 'MarkdownV2');
            $bot->answerCallbackQuery($updateId, AppString::get('settings.loading'));
        } catch(Exception $e) {}

        $caption = new Caption(AppString::get('game.started'), null, 50);
        Table::send($game, $bot, $caption);
    }

    private function getColoredCards(array $shuffledCards, string $firstTeam) {
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

        return $shuffledCards;
    }

}