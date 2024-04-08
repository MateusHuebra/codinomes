<?php

namespace App\UpdateHandlers;

use App\Events\AddChat;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;

class Messages implements UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client {
        $client->callbackQuery(function (Update $update) use ($bot) {
            $message = $update->getMessage();
            AppString::setLanguage($message);

            if($message->getNewChatMembers()) {
                foreach($message->getNewChatMembers() as $newMember) {
                    if($newMember->getId()===env('TG_BOTID')) {
                        call_user_func(AddChat::getEvent($bot), $message);
                        return;
                    }
                }
            } else if($message->getLeftChatMember()) {

            }
        });

        return $client;
    }

}