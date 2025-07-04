<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Badges implements Action
{

    public function run(Update $update, BotApi $bot): Void
    {
        $userId = $update->getReplyToMessage()
            ? $update->getReplyToMessageFromId()
            : $update->getFromId();

        if (!$user = User::find($userId)) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_badges'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        $badges = $user->badges;
        if ($badges->isEmpty()) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_badges'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $text = '';
        $badgeCount = count($badges);

        foreach ($badges as $index => $badge) {
            $text .= "\n>*「" . UserBadge::EMOJIS[$badge->badge_shortname] . '」 ' . AppString::getParsed('badges.' . $badge->badge_shortname) . '*';

            if ($index !== $badgeCount - 1) {
                $text .= "\n>  \-  " . AppString::get('badges.' . $badge->badge_shortname . '_info') . "\n>";
            }
        }

        $text .= "\n>  \-  " . AppString::get('badges.' . $badge->badge_shortname . '_info') . '||';

        $title = AppString::get('badges.from', [
            'user' => $user->name,
        ], null, true);
        $title .= "**";

        $text = $title . $text . PHP_EOL . AppString::get('badges.footer');

        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }
}
