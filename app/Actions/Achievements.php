<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Achievements implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $userId = $update->getReplyToMessage() ? $update->getReplyToMessageFromId() : $update->getFromId();
        
        if(!$user = User::find($userId)) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_achievements'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        $achievements = $user->achievements;
        if($achievements->isEmpty()) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_achievements'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $text = AppString::get('achievements.from', [
            'user' => $user->name            
        ], null, true);
        $text.= "\n**";

        foreach(UserAchievement::AVAILABLE as $achievement) {
            if($achievements->contains('achievement_id', $achievement)) {
                $text.= '>*'.AppString::get('achievements.'.$achievement).'*';
                $text.= "\n>  \-  ".AppString::get('achievements.'.$achievement.'_info')."\n>\n";
            }
        }
        $text.= '>||';

        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}