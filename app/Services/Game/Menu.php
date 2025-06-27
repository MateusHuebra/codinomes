<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\GameTeamColor;
use App\Models\TeamColor;
use App\Models\User;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu {

    private static $user = null;
    
    static function send(Game $game, BotApi $bot, User $user = null, bool $forceResend = false) : Void {
        self::setUserVar($user);

        $game->refresh();
        $hasRequiredPlayers = $game->hasRequiredPlayers();
        $hasRequiredNumberOfPlayers = $hasRequiredPlayers ? true : $game->users->count() >= 4;
        $textMessage = self::getLobbyText($game, true);
        $textMessage.= $game->mode == Game::COOP ? '' : AppString::get('game.choose_role');
        $keyboard = self::getKeyboard($game, $hasRequiredPlayers, $hasRequiredNumberOfPlayers);

        if($game->lobby_message_id !== null) {
            try {
                if($forceResend) {
                    throw new Exception();
                }
                $bot->editMessageText($game->chat_id??$game->creator_id, $game->lobby_message_id, $textMessage, 'MarkdownV2', false, $keyboard);
                return;
            } catch(Exception $e) {
                if($e->getMessage()=='Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message') {
                    return;
                }
                $bot->tryToDeleteMessage($game->chat_id??$game->creator_id, $game->lobby_message_id);
            }
        }

        $message = $bot->sendMessage($game->chat_id??$game->creator_id, $textMessage, 'MarkdownV2', false, null, $keyboard);
        $bot->tryToPinChatMessage($game->chat_id??$game->creator_id, $message->getMessageId());
        $game->lobby_message_id = $message->getMessageId();
        $game->save();
    }

    private static function setUserVar(User $user = null) {
        if($user) {
            self::$user = $user;
        }
    }

    public static function getLobbyText(Game $game, bool $showInfo = false, string $winner = null) {
        $language = ($game->chat ?? $game->creator)->language;
        $textMessage = AppString::get('game.mode', [
            'mode' => AppString::getParsed('mode.'.$game->mode),
            'info' => ($showInfo ? '/info' : ''),
            'emoji' => Game::MODES[$game->mode]
        ], $language);
        $textMessage.= $game->getTeamAndPlayersList($winner);
        return $textMessage;
    }

    private static function getKeyboard(Game $game, bool $hasRequiredPlayers, bool $hasRequiredNumberOfPlayers) {
        $buttonsArray = [];
        $buttonsArray = self::getFirstButtons($game, $buttonsArray, $hasRequiredNumberOfPlayers);

        if($game->menu) {
            //show nothing

        } else if($hasRequiredPlayers) {
            $buttonsArray[] = [
                [
                    'text' => AppString::get('game.start'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::START_GAME
                    ])
                ]
            ];

        } else if($game->mode == Game::COOP) {
            $botUsername = env('APP_ENV')=='local' ? 'KianTestsBot' : 'CodinomesBot';
            $url = 'https://t.me/share/url?url='
                    .rawurlencode("https://t.me/$botUsername?start=coop_{$game->creator_id}_{$game->id}")
                    .'&text='
                    .rawurlencode(AppString::get('game.invite_coop_text'));
            $buttonsArray[] = [
                [
                    'text' => AppString::get('game.invite_coop'),
                    'url' => $url
                ]
            ];
        }

        return new InlineKeyboardMarkup($buttonsArray);
    }

    private static function getFirstButtons(Game $game, Array $buttonsArray, bool $hasRequiredNumberOfPlayers) {
        if($game->mode != Game::COOP) {
            $buttonsArray[] = [
                [
                    'text' => TeamColor::where('shortname', $game->getColor('a'))->first()->emoji.' '.AppString::get('game.master'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'a',
                        CDM::ROLE => CDM::MASTER
                    ])
                ],
                [
                    'text' => AppString::get('game.agents').' '.TeamColor::where('shortname', $game->getColor('a'))->first()->emoji,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'a',
                        CDM::ROLE => CDM::AGENT
                    ])
                ]
            ];
            $buttonsArray[] = [
                [
                    'text' => TeamColor::where('shortname', $game->getColor('b'))->first()->emoji.' '.AppString::get('game.master'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'b',
                        CDM::ROLE => CDM::MASTER
                    ])
                ],
                [
                    'text' => AppString::get('game.agents').' '.TeamColor::where('shortname', $game->getColor('b'))->first()->emoji,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'b',
                        CDM::ROLE => CDM::AGENT
                    ])
                ]
            ];
        }
        
        if($game->mode == Game::TRIPLE) {
            $buttonsArray[] = [
                [
                    'text' => TeamColor::where('shortname', $game->getColor('c'))->first()->emoji.' '.AppString::get('game.master'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'c',
                        CDM::ROLE => CDM::MASTER
                    ])
                ],
                [
                    'text' => AppString::get('game.agents').' '.TeamColor::where('shortname', $game->getColor('c'))->first()->emoji,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'c',
                        CDM::ROLE => CDM::AGENT
                    ])
                ]
            ];
        }

        $line = [];
        if(!$game->menu || $game->isMenu('color')) {
            $line[] = [
                'text' => '🎨  '.($game->menu == 'color' ? 'X' : '\\/'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => ($game->menu == 'color' ? null : 'color')
                ])
            ];
        }
        
        if($hasRequiredNumberOfPlayers && !in_array($game->mode, [Game::TRIPLE, Game::COOP])) {
            $line[] = [
                'text' => '🎲',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SHUFFLE_PLAYERS
                ])
            ];
        }

        if($game->mode != Game::COOP) {
            $line[] = [
                'text' => '⚙️',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SETTINGS
                ])
            ];
        }
        
        $line[] = [
            'text' => AppString::get('game.leave'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::LEAVE_GAME
            ])
        ];
        $buttonsArray[] = $line;

        if($game->isMenu('color') && self::$user) {
            $buttonsArray = TeamColor::addColorsToKeyboard(self::$user, $buttonsArray);
        }
        
        return $buttonsArray;
    }

}