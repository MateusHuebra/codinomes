<?php

namespace App\UpdateHandlers;

use App\Events\Chat\Add as AddChat;
use App\Events\Chat\Delete as DeleteChat;
use App\Services\AppString;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;

class Messages implements UpdateHandler {

    static function addEvents(Client $client, BotApi $bot) : Client {
        $client->on(function (Update $update) use ($bot) {
            $message = $update->getMessage();
            AppString::setLanguage($message);
            if($message->getNewChatMembers()) {
                foreach($message->getNewChatMembers() as $newMember) {
                    if($newMember->getId() == env('TG_BOTID')) {
                        call_user_func(AddChat::getEvent($bot), $message);
                        return;
                    }
                }
            } else if($message->getLeftChatMember()) {
                if($message->getLeftChatMember()->getId() == env('TG_BOTID')) {
                    call_user_func(DeleteChat::getEvent($bot), $message);
                    return;
                }
            }
        }, function () {
            return true;
        });

        return $client;
    }

}