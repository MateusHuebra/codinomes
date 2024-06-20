<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\Chat;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;


class Notify implements Action {
    
    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user || $user->status != 'actived') {
            $bot->sendMessage($update->getChatId(), AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($update->isType(Update::CALLBACK_QUERY)) {
            $this->handleCallbackQuery($update, $bot, $user);

        } else if($update->isChatType('supergroup')) {
            $this->handleForSupergroup($update, $bot, $user);

        } else if($update->isChatType('private')) {
            $this->handleForPrivate($update, $bot, $user);
        }
    }

    private function handleForSupergroup(Update $update, BotApi $bot, User $user) {
        $chat = $update->findChat();
        if(!$chat) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.must_be_supergroup'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $toggled = $chat->notifiableUsers()->toggle([$user->id]);
        if(count($toggled['attached']) == 1) {
            $bot->setMessageReaction($chat->id, $update->getMessageId(), '✍️');
        } else {
            $bot->setMessageReaction($chat->id, $update->getMessageId(), '🙊');
        }
    }

    private function handleForPrivate(Update $update, BotApi $bot, User $user) {
        if($user->chatsToNotify()->count() == 0) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_notify'));
            return;
        }

        $keyboard = $this->getKeyboard($user);
        $bot->sendMessage($update->getChatId(), AppString::get('settings.notify_list'), null, false, null, $keyboard);
    }

    private function handleCallbackQuery(Update $update, BotApi $bot, User $user) {
        $data = CDM::toArray($update->getData());
        $chat = Chat::find($data[CDM::VALUE]);
        $chat->notifiableUsers()->toggle([$user->id]);

        if($user->chatsToNotify()->count() == 0) {
            $bot->editMessageText($update->getChatId(), $update->getMessageId(), AppString::get('error.no_notify'));
            return;
        }

        $keyboard = $this->getKeyboard($user);
        $bot->editMessageReplyMarkup($update->getChatId(), $update->getMessageId(), $keyboard);
    }

    private function getKeyboard(User $user) {
        $chatsToNotify = $user->chatsToNotify;
        $keyboard = [];
        foreach ($chatsToNotify as $chat) {
            $keyboard[] = [[
                    'text' => $chat->title,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::TURN_NOTIFY_OFF,
                        CDM::VALUE => $chat->id
                    ])
                ]];
        }
        return new InlineKeyboardMarkup($keyboard);
    }

}