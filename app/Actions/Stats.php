<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Models\User;
use App\Services\AppString;
use TelegramBot\Api\BotApi;

class Stats implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        $userId = $update->getReplyToMessage() ? $update->getReplyToMessageFromId() : $update->getFromId();
        
        if(!$user = User::find($userId)) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_enough_stats'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if(!$user->currentGame() || !$stats = $user->stats) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.no_enough_stats'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if($user->currentGame()->mode == 'mystery') {
            $bot->sendMessage($update->getChatId(), AppString::get('error.hidden_on_mystery'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $totalGames = $stats->games_as_master + $stats->games_as_agent;
        $totalWins = $stats->wins_as_master + $stats->wins_as_agent;
        $totalWinsPercent = ($totalGames == 0) ? 0 : number_format(($totalWins / $totalGames) * 100, 1);
        $winsAsMasterPercent = ($stats->games_as_master == 0) ? 0 : number_format(($stats->wins_as_master / $stats->games_as_master) * 100, 1);
        $winsAsAgentPercent = ($stats->games_as_agent == 0) ? 0 : number_format(($stats->wins_as_agent / $stats->games_as_agent) * 100, 1);
        
        $text = AppString::get('stats.general', [
            "name" => $user->name,
            "totalGames" => $totalGames,
            "totalWins" => $totalWins,
            "totalWinsPercent" => $totalWinsPercent,
            "winsAsMaster" => $stats->wins_as_master,
            "gamesAsMaster" => $stats->games_as_master,
            "winsAsMasterPercent" => $winsAsMasterPercent,
            "winsAsAgent" => $stats->wins_as_agent,
            "gamesAsAgent" => $stats->games_as_agent,
            "winsAsAgentPercent" => $winsAsAgentPercent,
            "attemptsOnAllyStreak" => $stats->attempts_on_ally_streak,
            "attemptsOnAlly" => $stats->attempts_on_ally,
            "attemptsOnOpponent" => $stats->attempts_on_opponent,
            "attemptsOnWhite" => $stats->attempts_on_white,
            "attemptsOnBlack" => $stats->attempts_on_black,
            "hintedToAllyStreak" => $stats->hinted_to_ally_streak,
            "hintedToAlly" => $stats->hinted_to_ally,
            "hintedToOpponent" => $stats->hinted_to_opponent,
            "hintedToWhite" => $stats->hinted_to_white,
            "hintedToBlack" => $stats->hinted_to_black
        ], null, true);

        $bot->sendMessage($update->getChatId(), $text, 'MarkdownV2', false, $update->getMessageId(), null, false, null, null, true);
    }

}