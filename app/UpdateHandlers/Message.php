<?php

namespace App\UpdateHandlers;

use App\Actions\Chat\Add as AddChat;
use App\Actions\Chat\Delete as DeleteChat;
use App\Actions\Chat\Settings;
use App\Actions\Game\ChosenGuess;
use App\Actions\Game\ChosenHint;
use App\Actions\GetColors;
use App\Actions\Game\ConfirmSkip;
use App\Actions\Game\Create;
use App\Actions\Game\History;
use App\Actions\Game\Stop;
use App\Actions\Help;
use App\Actions\Language\Get;
use App\Actions\Notify;
use App\Actions\Pack\WebApp;
use App\Actions\Ping;
use App\Actions\Start;
use App\Actions\Stats;
use App\Actions\Test;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Message as MessageType;

class Message implements UpdateHandler {

    public function getAction($update) {
        $commandMatches = $this->getCommand($update->getMessage());

        if($commandMatches) {
            $command = $commandMatches[1];
            $botUsername = $commandMatches[2];
            if(!$botUsername || $botUsername == '@'.env('TG_BOT_USERNAME')) {
                return $this->getActionForCommand($command);
            }

        } else {
            if($update->getNewChatMembers()) {
                foreach($update->getNewChatMembers() as $newMember) {
                    if($newMember->getId() == env('TG_BOT_ID')) {
                        return new AddChat;
                    }
                }
    
            } else if($update->getLeftChatMember()) {
                if($update->getLeftChatMember()->getId() == env('TG_BOT_ID')) {
                    return new DeleteChat;
                }
    
            } else {
                return $this->getActionForOrdinaryMessage($update);
            }
        }
    }

    private function getActionForOrdinaryMessage($update) {
        $user = $update->findUser();
        if($user && $game = $user->currentGame()) {
            if(
                $update->isChatType('private')
                &&
                $game->role == 'master'
                &&
                $user->currentGame()->player->role == 'master'
                &&
                $game->team == $user->currentGame()->player->team
            ) {
                return new ChosenHint;

            } else if (
                $update->isChatType('supergroup')
                &&
                $game->role == 'agent'
                &&
                $user->currentGame()->player->role == 'agent'
                &&
                $game->team == $user->currentGame()->player->team
            ) {
                return new ChosenGuess;
            }
        }
    }

    private function getActionForCommand(string $command) {
        if($command == 'start') {
            return new Start;

        } else if(in_array($command, ['new', 'novo'])) {
            return new Create;

        } else if(in_array($command, ['stop', 'parar'])) {
            return new Stop;

        } else if(in_array($command, ['history', 'historico'])) {
            return new History;

        } else if(in_array($command, ['settings', 'config'])) {
            return new Settings;

        } else if(in_array($command, ['color', 'cor'])) {
            return new GetColors;

        } else if(in_array($command, ['language', 'idioma'])) {
            return new Get;

        } else if(in_array($command, ['notify', 'notificar'])) {
            return new Notify;

        } else if(in_array($command, ['skip', 'pular'])) {
            return new ConfirmSkip;

        } else if(in_array($command, ['help', 'ajuda'])) {
            return new Help;

        } else if(in_array($command, ['help_roles', 'ajuda_papeis'])) {
            return new Help('roles');

        } else if(in_array($command, ['help_hints', 'ajuda_dicas'])) {
            return new Help('hints');

        } else if(in_array($command, ['help_colors', 'ajuda_cores'])) {
            return new Help('colors');

        } else if(in_array($command, ['help_commands', 'ajuda_comandos'])) {
            return new Help('commands');

        } else if(in_array($command, ['packs', 'pacotes'])) {
            return new WebApp;

        } else if($command == 'test') {
            return new Test;

        } else if($command == 'stats') {
            return new Stats;

        } else if($command == 'ping') {
            return new Ping;
        }
    }

    private function getCommand(MessageType $message) {
        if (is_null($message) || !strlen($message->getText())) {
            return false;
        }

        preg_match(Client::REGEXP.'mi', $message->getText(), $matches);

        return $matches;
    }

}