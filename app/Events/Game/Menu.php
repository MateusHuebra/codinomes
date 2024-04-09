<?php

namespace App\Events\Game;

use App\Events\Event;
use App\Models\Game;
use App\Services\Telegram\BotApi;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\Types\Message;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Menu implements Event {

    const RESEND = 'resend';
    const EDIT = 'edit';

    static function getEvent(BotApi $bot) : callable {
        return function (Message $message) use ($bot) {
            null;
        };
    }
    
    static function send(int $gameId, BotApi $bot, string $action = null, int $messageId = null) {
        $game = Game::find($gameId);
        $users = $game->users;
        $masterA = $users->where('team', 'a')->where('role', 'master')->first();
        $agentsA = $users->where('team', 'a')->where('role', 'agent');
        $masterB = $users->where('team', 'b')->where('role', 'master')->first();
        $agentsB = $users->where('team', 'b')->where('role', 'agent');
        $empty = AppString::get('game.empty');
        $stringMasterA = $masterA->name??$empty;
        $stringMasterB = $masterB->name??$empty;
        $stringAgentsA = self::getAgentsStringList($agentsA, $empty);
        $stringAgentsB = self::getAgentsStringList($agentsB, $empty);

        $textMessage = AppString::get('game.teams_lists', [
            'master_a' => $stringMasterA,
            'agents_a' => $stringAgentsA,
            'master_b' => $stringMasterB,
            'agents_b' => $stringAgentsB,
            'a' => Game::A_EMOJI,
            'b' => Game::B_EMOJI
        ], null, true);

        $keyboard = new InlineKeyboardMarkup([
            [
                [
                    'text' => Game::A_EMOJI.' mestre',
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'a',
                        CDM::ROLE => CDM::MASTER
                    ])
                ],
                [
                    'text' => 'mestre '.Game::B_EMOJI,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'b',
                        CDM::ROLE => CDM::MASTER
                    ])
                ]
            ],
            [
                [
                    'text' => Game::A_EMOJI.' agentes',
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'a',
                        CDM::ROLE => CDM::AGENT
                    ])
                ],
                [
                    'text' => 'agentes '.Game::B_EMOJI,
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SELECT_TEAM_AND_ROLE,
                        CDM::TEAM => 'b',
                        CDM::ROLE => CDM::AGENT
                    ])
                ]
            ]
        ]);

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

    private static function getAgentsStringList($agents, string $empty) : String {
        if($agents->count()==0) {
            return $empty;
        }
        $namesArray = [];
        foreach($agents as $agent) {
            $namesArray[] = $agent->name;
        }
        return implode(', ', $namesArray);
    }

}