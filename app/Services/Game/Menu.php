<?php

namespace App\Services\Game;

use App\Models\Game;
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

        $empty = '_'.AppString::get('game.empty').'_';
        $textMessage = AppString::get('game.teams_lists', [
            'master_a' => $masterA->get()->toMentionList()??$empty,
            'agents_a' => $agentsA->get()->toMentionList()??$empty,
            'master_b' => $masterB->get()->toMentionList()??$empty,
            'agents_b' => $agentsB->get()->toMentionList()??$empty,
            'a' => Game::TEAM['a']['emoji'],
            'b' => Game::TEAM['b']['emoji']
        ]);

        $keyboard = self::getKeyboard($hasRequiredPlayers);

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
            $bot->sendMessage($game->chat_id, $textMessage, 'MarkdownV2', false, null, $keyboard);
        }
    }

    private static function getKeyboard(bool $hasRequiredPlayers) {
        $buttonsArray = [];
        $buttonsArray[] = [
            [
                'text' => Game::TEAM['a']['emoji'].' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::TEAM['a']['emoji'],
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::AGENT
                ])
            ]
        ];
        $buttonsArray[] = [
            [
                'text' => Game::TEAM['b']['emoji'].' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'b',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::TEAM['b']['emoji'],
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'b',
                    CDM::ROLE => CDM::AGENT
                ])
            ]
        ];
        $buttonsArray[] = [
            [
                'text' => AppString::get('game.leave'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::LEAVE_GAME
                ])
            ]
        ];

        if($hasRequiredPlayers) {
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

}