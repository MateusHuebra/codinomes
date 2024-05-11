<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Chat;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class Settings implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        
        $chat = $update->findChat();
        if(!$chat) {
            $add = new Add();
            $add->run($update, $bot);
            $chat = $update->findChat();
        }

        $user = $update->findUser();
        if(!$chat->hasPermission($user, $bot)) {
            if($update->isType(Update::CALLBACK_QUERY)) {
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
            } else {
                $bot->sendMessage($chat->id, AppString::get('error.admin_only'), null, false, $update->getMessageId(), null, false, null, null, true);
            }
            return;
        }

        $text = AppString::get('settings.chat');
        $keyboard = self::getKeyboard($chat);

        $bot->sendMessage($chat->id, $text, null, false, $update->getMessageId(), $keyboard, false, null, null, true);
    }

    public static function getKeyboard(Chat $chat) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('settings.admin_only'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_ADMIN_ONLY,
                        CDM::VALUE => CDM::INFO
                    ])
                ],
                [
                    'text' => AppString::get('settings.'.($chat->admin_only?'on':'off')),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_ADMIN_ONLY
                    ])
                ]
            ],
            [
                [
                    'text' => AppString::get('settings.timer'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_TIMER,
                        CDM::VALUE => CDM::INFO
                    ])
                ],
                [
                    'text' => $chat->timer ? $chat->timer.' '.AppString::get('time.minutes') : AppString::get('settings.off'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_TIMER
                    ])
                ]
            ],
            [
                [
                    'text' => 'Gerenciar Pacotes (em breve)',
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::IGNORE
                    ])
                ]
            ]
        ]);
    }

}