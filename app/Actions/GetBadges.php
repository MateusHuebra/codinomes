<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\User;
use App\Models\UserBadge;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class GetBadges implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.user_not_registered', null), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if(!$user->badges()->exists()){
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_badges', null), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($user->active_badge) {
            $badge = UserBadge::EMOJIS[$user->active_badge];
        } else {
            $badge = '-';
        }

        $buttonsArray = $this->addBadgesToKeyboard($user);
        $buttonsArray[] = [[
            'text' => AppString::get('settings.turn_off'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::CHANGE_ACTIVE_BADGE
            ])
        ]];
        $keyboard = new InlineKeyboardMarkup($buttonsArray);
        $bot->sendMessage($update->getChatId(), AppString::get('badges.active', ['badge' => $badge], $user->language), null, false, $update->getMessageId(), $keyboard, false, null, null, true);
    }

    private function addBadgesToKeyboard(User $user) {
        $buttonsArray = [];
        foreach ($user->badges as $badge) {
            $badgeShortname = $badge->badge_shortname;
            $emoji = UserBadge::EMOJIS[$badgeShortname];

            $buttonsArray[] = [[
                'text' => $emoji . ' ' . AppString::get('badges.' . $badgeShortname, null, $user->language),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::CHANGE_ACTIVE_BADGE,
                    CDM::TEXT => $badgeShortname
                ])
            ]];
        }
        return $buttonsArray;
    }

}