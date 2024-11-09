<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GameTeamColor;
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
        $game->triggerLock();
        
        if(
            !($game->mode != Game::COOP && $game->role == 'master' && $player->role == 'master' && $player->team == $game->team)
            && 
            !($game->mode == Game::COOP && ($game->role == null || $game->role == $player->role))
        ) {
            return;
        }

        $regex = "/\*[".implode('', GameTeamColor::COLORS)."👥]+ (?<hint>[\w\S\- ]{1,20} [0-9∞]+)\*(\R>  - .+)+$/u";
        if($game->mode == Game::COOP && ($game->role != null && !preg_match($regex, $game->history))) {
            $bot->sendMessage($update->getFromId(), AppString::get('error.guess_or_skip_before_hint'));
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
            $bot->tryToSetMessageReaction($update->getChatId(), $update->getMessageId(), '👍');
        } else if ($update->isType(Update::CHOSEN_INLINE_RESULT)) {
            $data = CDM::toArray($update->getResultId());
        }
        
        if($game->mode == Game::COOP) {
            if($game->role == $player->role) {
                if($game->attempts_left <= 1) {
                    $bot->sendMessage($update->getFromId(), AppString::get('error.not_enough_rounds_to_hint'));
                    return;
                }
                $attemptsLeft = $game->attempts_left - 1;
            }
        } else if(in_array($data[CDM::NUMBER], ['∞', 0])) {
            $attemptsLeft = null;
        } else {
            $attemptsLeft = $data[CDM::NUMBER];
        }
        
        $nextRole = $player->role == 'agent' ? 'master' : 'agent';
        $hint = $data[CDM::TEXT].' '.$data[CDM::NUMBER];
        $color = $game->getColor($player->team);
        $emoji = $player->role == 'master' ? GameTeamColor::COLORS[$color] : '👥';
        $historyLine = $emoji.' '.$hint;
        $game->addToHistory('*'.$historyLine.'*');
        $game->updateStatus('playing', $player->team, $nextRole, $attemptsLeft??null);

        $titleSize = strlen($hint) >= 16 ? 40 : 50;
        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], null, true);

        $captionText = $hint;
        if($game->mode == Game::EMOJI) {
            $isEmoji = true;
            $text = $mention.' '.$emoji.' '.$data[CDM::NUMBER].':';
            echo $text;
        } else {
            $isEmoji = false;
            $text = $mention.' '.$emoji.' '.AppString::parseMarkdownV2($hint);
        }
        
        $caption = new Caption($captionText, null, $titleSize, $isEmoji);
        
        try {
            if($game->mode == Game::COOP) {
                $partner = $nextRole == 'agent' ? $game->getPartner() : $game->creator;
                $bot->sendMessage($partner->id, $text, 'MarkdownV2', false, null, null, true);
            } else {
                $bot->sendMessage($game->chat_id, $text, 'MarkdownV2', false, null, null, true);
                if($isEmoji) {
                    $bot->sendMessage($game->chat_id, $data[CDM::TEXT]);
                }
            }
            
        } catch(Exception $e) {}
        Table::send($game, $bot, $caption);
        $game->unlockGame();
    }

}