<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\GameTeamColor;
use App\Models\TeamColor;
use Exception;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use App\Services\CallbackDataManager as CDM;

class SetColor implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            $bot->answerCallbackQuery($update->getCallbackQueryId());
            return;
        }

        $data = CDM::toArray($update->getData());

        if(isset($data[CDM::TEXT])) {
            if(!TeamColor::isColorAllowedToUser($user, $data[CDM::TEXT], true)) {
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $update->getFromId(), 'error.color_taken');
                return;
            }

            $user->default_color = $data[CDM::TEXT];
            $color = TeamColor::where('shortname', $data[CDM::TEXT])->first()->emoji;
            
        } else {
            $user->default_color = null;
            $color = AppString::get('settings.off', null, $user->language);
        }
        $user->save();

        $mention = AppString::get('game.mention', [
            'name' => $user->name,
            'id' => $user->id
        ], $user->language, true);
        $text = AppString::get('color.defined', [
            'mention' => $mention,
            'color' => $color
        ], $user->language);
        
        try {
            $bot->editMessageText($update->getChatId(), $update->getMessageId(), $text, 'MarkdownV2');
        } catch(Exception $e) {
            $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2');
        }
    }

}