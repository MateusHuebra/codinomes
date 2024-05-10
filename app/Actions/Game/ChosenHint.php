<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class ChosenHint implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $game = $user->game;
        
        if(!(($game->status=='master_a' && $user->team=='a' && $user->role=='master') || ($game->status=='master_b' && $user->team=='b' && $user->role=='master'))) {
            return;
        }

        if($update->isType(Update::MESSAGE)) {
            $hint = new Hint;
            $data = CDM::toArray($hint->run($update, $bot, $update->getMessageText()));
            if($data[CDM::EVENT] == CDM::IGNORE) {
                $bot->tryToSetMessageReaction($update->getChatId(), $update->getMessageId(), '👎');
                return;
            }
            $bot->tryToSetMessageReaction($update->getChatId(), $update->getMessageId(), '👍');
        } else if ($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            $data = CDM::toArray($update->getResultId());
        }
        
        $hint = $data[CDM::TEXT].' '.$data[CDM::NUMBER];
        $color = ($user->team=='a') ? $game->color_a : $game->color_b;
        $emoji = Game::COLORS[$color];
        $historyLine = $emoji.' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('agent_'.$user->team);
        if(in_array($data[CDM::NUMBER], ['∞', 0])) {
            $game->attempts_left = null;
        } else {
            $game->attempts_left = $data[CDM::NUMBER];
        }
        $game->save();

        $caption = new Caption($hint, null, 50);
        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], null, true);
        $text = $emoji.' '.AppString::get('game.hinted', [
            'user' => $mention,
            'hint' => AppString::parseMarkdownV2($hint)
        ]);
        
        try {
            $bot->sendMessage($game->chat_id, $text, 'MarkdownV2', false, null, null, true);
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
    }

}