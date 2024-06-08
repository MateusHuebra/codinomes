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

        $text = '';
        $from = $to = 0;
        foreach(UserAchievement::AVAILABLE as $achievement) {
            $to++;
            if($achievements->contains('achievement_id', $achievement)) {
                $from++;
                $text.= '>*\[ '.AppString::get('achievements.'.$achievement).' \]*';
                $text.= "\n>  \-  ".AppString::get('achievements.'.$achievement.'_info')."\n>\n";
            }
        }

        $title = AppString::get('achievements.from', [
            'user' => $user->name,
            'from' => $from,
            'to' => $to
        ], null, true);
        $title.= "\n**";

        $text = $title.$text.'>||';

        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}