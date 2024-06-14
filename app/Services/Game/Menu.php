<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu {
    
    static function send(Game $game, BotApi $bot, bool $forceResend = false) : Void {
        $game->refresh();
        $hasRequiredPlayers = $game->hasRequiredPlayers();
        $textMessage = self::getLobbyText($game, true) . AppString::get('game.choose_role');
        $keyboard = self::getKeyboard($hasRequiredPlayers, $game);

        if($game->lobby_message_id !== null) {
            try {
                if($forceResend) {
                    throw new Exception();
                }
                $bot->editMessageText($game->chat_id, $game->lobby_message_id, $textMessage, 'MarkdownV2', false, $keyboard);
                return;
            } catch(Exception $e) {
                if($e->getMessage()=='Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message') {
                    return;
                }
                $bot->tryToDeleteMessage($game->chat_id, $game->lobby_message_id);
            }
        }

        $message = $bot->sendMessage($game->chat_id, $textMessage, 'MarkdownV2', false, null, $keyboard);
        $bot->tryToPinChatMessage($game->chat_id, $message->getMessageId());
        $game->lobby_message_id = $message->getMessageId();
        $game->save();
    }

    public static function getLobbyText(Game $game, bool $showInfo = false) {
        $textMessage = AppString::get('game.mode', [
            'mode' => AppString::get('mode.'.$game->mode),
            'info' => ($showInfo ? '/info' : ''),
            'emoji' => Game::MODES[$game->mode]
        ]);
        $textMessage.= $game->getTeamAndPlayersList();
        return $textMessage;
    }

    private static function getKeyboard(bool $hasRequiredPlayers, Game $game) {
        $buttonsArray = [];
        $buttonsArray = self::getFirstButtons($game, $buttonsArray);

        if($hasRequiredPlayers && !$game->menu) {
            $buttonsArray[] = [
                [
                    'text' => AppString::get('game.start'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::START_GAME
                    ])
                ]
            ];
        }

        return new InlineKeyboardMarkup($buttonsArray);
    }

    private static function getFirstButtons(Game $game, Array $buttonsArray) {
        $buttonsArray[] = [
            [
                'text' => Game::COLORS[$game->color_a].' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::COLORS[$game->color_a],
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::AGENT
                ])
            ]
        ];
        $buttonsArray[] = [
            [
                'text' => Game::COLORS[$game->color_b].' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'b',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::COLORS[$game->color_b],
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'b',
                    CDM::ROLE => CDM::AGENT
                ])
            ]
        ];

        $line = [];
        if(!$game->menu || $game->isMenu('color')) {
            $line[] = [
                'text' => AppString::get('game.color').'  '.($game->menu == 'color' ? 'X' : '\\/'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => ($game->menu == 'color' ? null : 'color')
                ])
            ];
        }
        
        $line[] = [
            'text' => AppString::get('game.settings'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS
            ])
        ];
        $line[] = [
            'text' => AppString::get('game.leave'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::LEAVE_GAME
            ])
        ];
        $buttonsArray[] = $line;

        if($game->isMenu('color')) {
            $buttonsArray = self::addColorsToKeyboard($buttonsArray);
        }
        
        return $buttonsArray;
    }

    public static function addColorsToKeyboard(array $buttonsArray = [], string $event = CDM::CHANGE_COLOR) {
        $line = [];
        $i = 0;
        foreach(Game::COLORS as $color => $emoji) {
            $i++;
            if(in_array($color, ['white', 'black'])) {
                continue;
            }
            $line[] = [
                'text' => $emoji,
                'callback_data' => CDM::toString([
                    CDM::EVENT => $event,
                    CDM::TEXT => $color
                ])
            ];
            if($i>=5) {
                $buttonsArray[] = $line;
                $line = [];
                $i = 0;
            }
        }
        if(!empty($line)) {
            $buttonsArray[] = $line;
        }
        return $buttonsArray;
    }

}