<?php

namespace App\Services\Game;

use App\Models\Chat;
use App\Models\Game;
use App\Models\Pack;
use App\Models\User;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use Exception;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu {
    
    static function send(Game $game, BotApi $bot, int $messageId = null, User $user = null) : Void {
        $game->refresh();
        $hasRequiredPlayers = $game->hasRequiredPlayers();
        $textMessage = $game->getTeamAndPlayersList().AppString::get('game.choose_role');
        $keyboard = self::getKeyboard($hasRequiredPlayers, $game, $user);

        if($messageId !== null) {
            try {
                $bot->editMessageText($game->chat_id, $messageId, $textMessage, 'MarkdownV2', false, $keyboard);
                return;
            } catch(Exception $e) {
                if($e->getMessage()=='Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message') {
                    return;
                }
            }
        }

        $message = $bot->sendMessage($game->chat_id, $textMessage, 'MarkdownV2', false, null, $keyboard);
        $bot->tryToPinChatMessage($game->chat_id, $message->getMessageId());
        $game->message_id = $message->getMessageId();
        $game->save();
    }

    private static function getKeyboard(bool $hasRequiredPlayers, Game $game, User $user = null) {
        $buttonsArray = [];
        $buttonsArray = self::getFirstButtons($game, $buttonsArray);

        if($game->isMenu('packs')) {
            $buttonsArray[] = [[
                'text' => AppString::get('game.packs_actived'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_actived',
                ])
            ]];
            $buttonsArray[] = [[
                'text' => AppString::get('game.packs_official'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_official',
                ])
            ]];
            $buttonsArray[] = [[
                'text' => AppString::get('game.packs_users'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_users',
                ])
            ]];
            $buttonsArray[] = [[
                'text' => AppString::get('game.packs_mine'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_mine',
                ])
            ]];

        } else if($game->isMenu('packs', 'actived')) {
            $buttonsArray = self::getActivedPacks($game->chat, $buttonsArray);

        } else if($game->isMenu('packs', 'official')) {
            $packs = Pack::where('user_id', null);
            $buttonsArray = self::getPacks($game, $buttonsArray, $packs);

        } else if($game->isMenu('packs', 'users')) {
            $packs = Pack::where('status', 'public')->whereNotNull('user_id');
            $buttonsArray = self::getPacks($game, $buttonsArray, $packs);
            
        } else if($game->isMenu('packs', 'mine')) {
            $packs = Pack::where('user_id', $user->id);
            $buttonsArray = self::getPacks($game, $buttonsArray, $packs);
        }

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

        if($game->isMenu('color')) {
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
                        CDM::EVENT => CDM::CHANGE_COLOR,
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
        }

        $line = [];
        if(!$game->menu || $game->isMenu('color')) {
            $line[] = [
                'text' => AppString::get('game.color').'  '.($game->menu == 'color' ? 'X' : '/\\'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => ($game->menu == 'color' ? null : 'color')
                ])
            ];
        }
        switch ($game->getMenu(true)) {
            case 'packs_actived':
                $text = AppString::get('game.packs_actived');
                break;
            case 'packs_official':
                $text = AppString::get('game.packs_official');
                break;
            case 'packs_users':
                $text = AppString::get('game.packs_users');
                break;
            case 'packs_mine':
                $text = AppString::get('game.packs_mine');
                break;
            default:
                $text = AppString::get('game.packs');
                break;
        }
        $line[] = [
            'text' => $text.' '.(strpos($game->menu, "packs")!==false ? 'X' : '\\/'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::MENU,
                CDM::TEXT => (strpos($game->menu, "packs")!==false ? null : 'packs')
            ])
        ];
        $line[] = [
            'text' => AppString::get('game.leave'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::LEAVE_GAME
            ])
        ];
        $buttonsArray[] = $line;
        return $buttonsArray;
    }

    private static function getActivedPacks(Chat $chat, Array $buttonsArray) {
        foreach ($chat->packs as $pack) {
            $buttonsArray[] = [[
                'text' => '> '.$pack->name.' ('.$pack->cards()->count().')',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::CHANGE_PACK,
                    CDM::TEXT => $pack->id,
                    CDM::NUMBER => 0
            ])
        ]];
        }
        
        $buttonsArray[] = [[
            'text' => 'Voltar',
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::MENU,
                CDM::TEXT => 'packs',
            ])
        ]];
        return $buttonsArray;
    }

    private static function getPacks(Game $game, Array $buttonsArray, $packs) {
        $chatPacks = $game->chat->packs;
        $take = 5;
        $skip = ($game->getMenuPage()) * $take;
        $packs = $packs->skip($skip)->take($take);
        $totalPages = $packs->count() / $take;

        foreach ($packs->get() as $pack) {
            if($chatPacks->find($pack->id)) {
                $text = '> '.$pack->name.' ('.$pack->cards()->count().')';
                $bool = 0;
            } else {
                $text = $pack->name.' ('.$pack->cards()->count().')';
                $bool = 1;
            }
            $buttonsArray[] = [[
                'text' => $text,
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::CHANGE_PACK,
                    CDM::TEXT => $pack->id,
                    CDM::NUMBER => $bool
            ])
        ]];
        }

        $line = [];
        if($game->getMenuPage()>0) {
            $line[] = [
                'text' => '<',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => $game->getMenu(true).':'.$game->getMenuPage()-1,
                ])
            ];
        }
        if($game->getMenuPage()<$totalPages-1) {
            $line[] = [
                'text' => '>',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => $game->getMenu(true).':'.$game->getMenuPage()+1,
                ])
            ];
        }
        $buttonsArray[] = $line;
        
        $buttonsArray[] = [[
            'text' => AppString::get('game.back'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::MENU,
                CDM::TEXT => 'packs',
            ])
        ]];
        return $buttonsArray;
    }

}