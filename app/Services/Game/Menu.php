<?php

namespace App\Services\Game;

use App\Models\Chat;
use App\Models\Game;
use App\Models\Pack;
use TelegramBot\Api\BotApi;
use App\Services\AppString;
use Exception;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu {

    const RESEND = 'resend';
    const EDIT = 'edit';
    
    static function send(Game $game, BotApi $bot, string $action = null, int $messageId = null) : Void {
        $game->refresh();

        $masterA = $game->users()->fromTeamRole('a', 'master');
        $agentsA = $game->users()->fromTeamRole('a', 'agent');
        $masterB = $game->users()->fromTeamRole('b', 'master');
        $agentsB = $game->users()->fromTeamRole('b', 'agent');

        if($masterA->count()==0 || $agentsA->count()==0 || $masterB->count()==0 || $agentsB->count()==0) {
            $hasRequiredPlayers = false;
        } else {
            $hasRequiredPlayers = true;
        }

        $teamA = mb_strtoupper(AppString::get('color.'.$game->color_a), 'UTF-8').' '.Game::COLORS[$game->color_a];
        $teamB = mb_strtoupper(AppString::get('color.'.$game->color_b), 'UTF-8').' '.Game::COLORS[$game->color_b];
        $empty = '_'.AppString::get('game.empty').'_';
        $textMessage = AppString::get('game.teams_lists', [
            'master_a' => $masterA->get()->toMentionList()??$empty,
            'agents_a' => $agentsA->get()->toMentionList()??$empty,
            'master_b' => $masterB->get()->toMentionList()??$empty,
            'agents_b' => $agentsB->get()->toMentionList()??$empty,
            'a' => $teamA,
            'b' => $teamB
        ]);

        $keyboard = self::getKeyboard($hasRequiredPlayers, $game);

        try {
            if($action == self::RESEND) {
                $bot->deleteMessage($game->chat_id, $messageId);
            }
            if ($action == self::EDIT) {
                $bot->editMessageText($game->chat_id, $messageId, $textMessage, 'MarkdownV2', false, $keyboard);
            } else {
                throw new Exception;
            }
        } catch(Exception $e) {
            if($e->getMessage()=='Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message') {
                return;
            }
            $message = $bot->sendMessage($game->chat_id, $textMessage, 'MarkdownV2', false, null, $keyboard);
            try {
                $bot->pinChatMessage($game->chat_id, $message->getMessageId());
            } catch(Exception $e) {}
            $game->message_id = $message->getMessageId();
            $game->save();
        }
    }

    private static function getKeyboard(bool $hasRequiredPlayers, Game $game) {
        $buttonsArray = [];
        $buttonsArray = self::getFirstButtons($game, $buttonsArray);

        if($game->menu == 'packs') {
            $buttonsArray[] = [[
                'text' => 'Packs Ativos',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_actived',
                ])
            ]];
            $buttonsArray[] = [[
                'text' => 'Packs Oficiais',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => 'packs_official',
                ])
            ]];
        } else if($game->menu == 'packs_actived') {
            $buttonsArray = self::getActivedPacks($game->chat, $buttonsArray);
        } else if($game->menu == 'packs_official') {
            $buttonsArray = self::getOfficialPacks($game->chat, $buttonsArray);
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

        if($game->menu == 'color') {
            $array = [];
            foreach(Game::COLORS as $color => $emoji) {
                $array[] = [
                    'text' => $emoji,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_COLOR,
                        CDM::TEXT => $color
                    ])
                ];
            }
            $buttonsArray[] = $array;
        }

        $line = [];
        if(!$game->menu || $game->menu == 'color') {
            $line[] = [
                'text' => AppString::get('game.color').'  '.($game->menu == 'color' ? 'X' : '/\\'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::MENU,
                    CDM::TEXT => ($game->menu == 'color' ? null : 'color')
                ])
            ];
        }
        switch ($game->menu) {
            case 'packs_actived':
                $text = AppString::get('game.packs_actived');
                break;
            case AppString::get('game.packs_official'):
                $text = 'Packs Oficiais';
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
                'text' => '{ '.$pack->name.' }',
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

    private static function getOfficialPacks(Chat $chat, Array $buttonsArray) {
        $chatPacks = $chat->packs;
        foreach (Pack::where('user_id', null)->get() as $pack) {
            if($chatPacks->find($pack->id)) {
                $text = '{ '.$pack->name.' }';
                $bool = 0;
            } else {
                $text = $pack->name;
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