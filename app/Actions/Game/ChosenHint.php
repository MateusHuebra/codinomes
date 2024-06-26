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
        $game = $user->currentGame();
        $player = $game->player;
        
        if(!($game->role == 'master' && $player->role == 'master' && $player->team == $game->team)) {
            return;
        }

        if($update->isType(Update::MESSAGE)) {
            if($update->getViaBot()) {
                $bot->sendMessage($user->id, AppString::get('error.dm_inline'));
                return;
            }
            $hint = new Hint;
            $data = CDM::toArray($hint->run($update, $bot, $update->getMessageText()));
            if($data[CDM::EVENT] == CDM::IGNORE) {
                $bot->sendMessage($user->id, AppString::get('error.wrong_hint_format_desc'));
                $bot->tryToSetMessageReaction($update->getChatId(), $update->getMessageId(), '👎');
                return;
            }
        } else if ($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            $data = CDM::toArray($update->getResultId());
        }
        
        $hint = $data[CDM::TEXT].' '.$data[CDM::NUMBER];
        $color = $game->getColor($player->team);
        $emoji = Game::COLORS[$color];
        $historyLine = $emoji.' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        
        $game->updateStatus('playing', $player->team, 'agent');
        if(in_array($data[CDM::NUMBER], ['∞', 0])) {
            $game->attempts_left = null;
        } else {
            $game->attempts_left = $data[CDM::NUMBER];
        }
        $game->save();

        $titleSize = strlen($hint) >= 16 ? 40 : 50;
        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], null, true);

        $captionText = $hint;
        if($game->mode == 'emoji') {
            $isEmoji = true;
            $text = $mention.' '.$emoji.' '.$data[CDM::NUMBER].':';
            echo $text;
        } else {
            $isEmoji = false;
            $text = $mention.' '.$emoji.' '.AppString::parseMarkdownV2($hint);
        }
        
        $caption = new Caption($captionText, null, $titleSize, $isEmoji);
        /*
        $text = $emoji.' '.AppString::get('game.hinted', [
            'user' => $mention,
            'hint' => AppString::parseMarkdownV2($hint)
        ]);
        */
        
        try {
            $bot->sendMessage($game->chat_id, $text, 'MarkdownV2', false, null, null, true);
            if($isEmoji) {
                $bot->sendMessage($game->chat_id, $data[CDM::TEXT]);
            }
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
    }

}