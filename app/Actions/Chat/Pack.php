<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Chat;
use App\Models\Pack as PackModel;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;
use App\Services\CallbackDataManager as CDM;

class Pack implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        $chat = $update->findChat();
        if(!$user) {
            return;
        }
        if(!$user || $user->status != 'actived') {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.user_not_registered');
            return;
        }
        if(!$chat->hasPermission($user, $bot)) {
            $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
            return;
        }

        $data = CDM::toArray($update->getData());
        $pack = PackModel::find($data[CDM::TEXT]);
        if(!$pack || !$chat) {
            return;
        }

        if($chat->click_to_save) {
            $this->activate($update, $bot, $chat, $user, $data, $pack);
        } else {
            $this->view($update->getCallbackQueryId(), $chat->id, $pack, $bot);
        }
        
    }

    private function activate(Update $update, BotApi $bot, Chat $chat, User $user, Array $data, PackModel $pack) {
        if($data[CDM::NUMBER]) {
            $chat->packs()->attach($pack->id);
        } else {
            $chat->packs()->detach($pack->id);
        }
        
        $settings = new Settings();
        $settings->prepareAndSend($update, $bot, $chat, $user);
    }

    private function view($callbackQueryId, int $chatId, PackModel $pack, BotApi $bot) {
        $text = '*'.AppString::parseMarkdownV2($pack->name).':*'.PHP_EOL.'**';
        $words = null;
        foreach($pack->cards as $card) {
            if($words !== null) {
                $words.= PHP_EOL;
            }
            $words.= '>'.AppString::parseMarkdownV2($card->text);
            if(strlen($words) >= 4000) {
                break;
            }
        }
        $text.= $words.'||';
        
        $bot->sendMessage($chatId, $text, 'MarkdownV2');
        $bot->answerCallbackQuery($callbackQueryId);
    }
}