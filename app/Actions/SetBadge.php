<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\UserBadge;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use App\Services\CallbackDataManager as CDM;

class SetBadge implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $data = CDM::toArray($update->getData());

        if(isset($data[CDM::TEXT])) {
            if(!$user->hasBadge($data[CDM::TEXT])) {
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $update->getFromId(), 'error.color_taken');
                return;
            }

            $user->active_badge = $data[CDM::TEXT];
            $badgeString = 'badges.' . $data[CDM::TEXT];

        } else {
            $user->active_badge = null;
            $badgeString = 'settings.off';
        }
        $user->save();

        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], $user->language, true);
        $text = AppString::get('badges.defined', [
            'mention' => $mention,
            'badge' => AppString::getParsed($badgeString, null, $user->language)
        ], $user->language);
        
        try {
            $bot->editMessageText($update->getChatId(), $update->getMessageId(), $text, 'MarkdownV2');
        } catch(Exception $e) {
            $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2');
        }
    }

}