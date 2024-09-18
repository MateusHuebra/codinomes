<?php

namespace App\Actions;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\GameTeamColor;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class GetColors implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $user = $update->findUser();
        if(!$user) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.user_not_registered', null, $user->language), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($user->default_color) {
            $color = GameTeamColor::COLORS[$user->default_color];
        } else {
            $color = '-';
        }

        $buttonsArray = Menu::addColorsToKeyboard([], $user->isVip(), CDM::CHANGE_DEFAULT_COLOR, true);
        $buttonsArray[] = [[
            'text' => AppString::get('settings.turn_off'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::CHANGE_DEFAULT_COLOR
            ])
        ]];
        $keyboard = new InlineKeyboardMarkup($buttonsArray);
        $bot->sendMessage($update->getChatId(), AppString::get('color.default', ['color' => $color], $user->language), null, false, $update->getMessageId(), $keyboard, false, null, null, true);
    }

}