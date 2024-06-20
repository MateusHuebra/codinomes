<?php

namespace App\Collections;

use App\Models\Game;
use App\Services\AppString;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class User extends Collection {

    public function getStringList(bool $mention = true, $separator = ', ') {
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

    public function notify(Game $game, BotApi $bot) {
        $game->refresh();
        $chat = $game->chat;
        $title = AppString::parseMarkdownV2($chat->title);
        $url = $chat->getUrl();
        $attachmentsToUpdate = [];

        foreach($this->items as $user) {
            $text = AppString::get('game.notification', [
                'title' => $title,
                'url' => $url
            ], $user->language);
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.open', null, $user->language),
                        'url' => $url
                    ]
                ]
            ]);

            if($user->pivot->message_id) {
                $bot->tryToDeleteMessage($user->id, $user->pivot->message_id);
            }

            if($game->creator_id === $user->id) {
                $attachmentsToUpdate[$user->id] = ['message_id' => null];
            } else {
                try {
                    $message = $bot->sendMessage($user->id, $text, 'MarkdownV2', true, null, $keyboard);
                    $attachmentsToUpdate[$user->id] = ['message_id' => $message->getMessageId()];
                } catch(Exception $e) {}
            }
        }

        $chat->notifiableUsers()->syncWithoutDetaching($attachmentsToUpdate);
    }
    
}