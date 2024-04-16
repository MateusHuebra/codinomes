<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\User;
use App\Services\Game\Table;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenGuess implements Action {

    public function run($update, BotApi $bot) : Void {
        $user = User::find($update->getFrom()->getId());
        $game = Game::find($user->game_id);
        
        if(!(($game->status=='agent_a' && $user->team=='a' && $user->role=='agent') || ($game->status=='agent_b' && $user->team=='b' && $user->role=='agent'))) {
            return;
        }
        if(is_null($game->attempts_left) || $game->attempts_left < 0) {
            return;
        }

        $data = CDM::toArray($update->getResultId());
        $card = GameCard::find($data[CDM::NUMBER]);
        $card->revealed = true;
        $card->save();
        if($card->team == $user->team) {
            $game->attempts_left--;
            if($game->attempts_left >= 0) {
                $this->next($user, $game, $bot, $card->id);
            } else {
                $this->skip($user, $game, $bot, $card->id);
            }

        } else if($card->team == 'x') {
            $this->endgame($user, $game, $bot, $card->id);

        } else {
            $this->skip($user, $game, $bot, $card->id);
        }
    }

    private function next(User $user, Game $game, $bot, int $cardId) {
        $game->updateStatus($game->status);
        $cardsLeft = $game->cards->where('team', $user->team)->where('revealed', false)->count();
        if($cardsLeft > 0) {
            Table::send($game, $bot, $cardId, null, false, null);
        
        } else {
            Table::send($game, $bot, $cardId, null, true, $user->team);
        }
    }

    private function skip(User $user, Game $game, $bot, int $cardId = null) {
        $nextStatus = 'master_'.$user->getEnemyTeam();
        $game->updateStatus($nextStatus);
        $game->attempts_left = null;
        $game->save();

        Table::send($game, $bot, $cardId);
    }

    private function endgame(User $user, Game $game, $bot, int $cardId) {
        Table::send($game, $bot, $cardId, null, true, $user->getEnemyTeam());
    }

}