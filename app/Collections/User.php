<?php

namespace App\Collections;

use App\Models\Chat;
use App\Services\AppString;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use TelegramBot\Api\BotApi;

class User extends Collection {

    public function getStringList(bool $mention, $separator = ', ') {
        if($this->count()==0) {
            return null;
        }
        $namesArray = [];
        foreach($this->items as $player) {
            if($mention) {
                $namesArray[] = AppString::get('game.mention', [
                    'name' => AppString::parseMarkdownV2($player->name),
                    'id' => $player->id
                ]);
            } else {
                $namesArray[] = AppString::parseMarkdownV2($player->name);
            }
        }
        return implode($separator, $namesArray);
    }

    public function notify(Chat $chat, BotApi $bot) {
        $chat->refresh();
        $text = AppString::get('game.notification', [
            'title' => AppString::parseMarkdownV2($chat->title),
            'url' => $chat->getUrl().'/'.$chat->game->message_id
        ]);
        $attachmentsToUpdate = [];
        foreach($this->items as $user) {
            if($user->pivot->message_id) {
                try {
                    $bot->deleteMessage($user->id, $user->pivot->message_id);
                } catch(Exception $e) {}
            }
            try {
                $message = $bot->sendMessage($user->id, $text, 'MarkdownV2');
                $attachmentsToUpdate[$user->id] = ['message_id' => $message->getMessageId()];
            } catch(Exception $e) {}
        }
        $chat->notifiableUsers()->syncWithoutDetaching($attachmentsToUpdate);
    }
    
}