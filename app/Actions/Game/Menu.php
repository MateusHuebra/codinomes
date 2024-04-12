<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Models\Game;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu implements Action {

    const RESEND = 'resend';
    const EDIT = 'edit';

    public function run($update, BotApi $bot) : Void {

    }
    
    static function send(Game $game, BotApi $bot, string $action = null, int $messageId = null) : Void {
        $game->refresh();
        $users = $game->users;
        $masterA = $users->where('team', 'a')->where('role', 'master')->first();
        $agentsA = $users->where('team', 'a')->where('role', 'agent');
        $masterB = $users->where('team', 'b')->where('role', 'master')->first();
        $agentsB = $users->where('team', 'b')->where('role', 'agent');
        $empty = AppString::get('game.empty');
        $stringMasterA = $masterA->name??null;
        $stringMasterB = $masterB->name??null;
        $stringAgentsA = self::getAgentsStringList($agentsA);
        $stringAgentsB = self::getAgentsStringList($agentsB);

        $textMessage = AppString::get('game.teams_lists', [
            'master_a' => $stringMasterA??$empty,
            'agents_a' => $stringAgentsA??$empty,
            'master_b' => $stringMasterB??$empty,
            'agents_b' => $stringAgentsB??$empty,
            'a' => Game::A_EMOJI,
            'b' => Game::B_EMOJI
        ], null, true);

        if(!is_null($stringMasterA) && !is_null($stringMasterB) && !is_null($stringAgentsA) && !is_null($stringAgentsB)) {
            $hasRequiredPlayers = true;
        } else {
            $hasRequiredPlayers = false;
        }

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

    private static function getAgentsStringList($agents) {
        if($agents->count()==0) {
            return null;
        }
        $namesArray = [];
        foreach($agents as $agent) {
            $namesArray[] = $agent->name;
        }
        return implode(', ', $namesArray);
    }

    private static function getKeyboard(bool $hasRequiredPlayers) {
        $buttonsArray = [];
        $buttonsArray[] = [
            [
                'text' => Game::A_EMOJI.' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::A_EMOJI,
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'a',
                    CDM::ROLE => CDM::AGENT
                ])
            ]
        ];
        $buttonsArray[] = [
            [
                'text' => Game::B_EMOJI.' '.AppString::get('game.master'),
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                    CDM::TEAM => 'b',
                    CDM::ROLE => CDM::MASTER
                ])
            ],
            [
                'text' => AppString::get('game.agents').' '.Game::B_EMOJI,
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