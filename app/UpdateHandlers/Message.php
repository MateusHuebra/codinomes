<?php

namespace App\UpdateHandlers;

use App\Actions\Chat\Add as AddChat;
use App\Actions\Chat\Delete as DeleteChat;
use App\Actions\Game\Create;
use App\Actions\Game\Stop;
use App\Actions\Language\Get;
use App\Actions\Ping;
use App\Actions\Start;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Message as MessageType;

class Message implements UpdateHandler {

    public function getAction($update) {
        $message = $update->getMessage();
        $commandMatches = $this->getCommand($message);

        if($commandMatches) {
            $command = $commandMatches[1];
            $botUsername = $commandMatches[2];
            if(!$botUsername || $botUsername == '@'.env('TG_BOT_USERNAME')) {
                return $this->getActionForCommand($command);
            }

        } else {
            if($message->getNewChatMembers()) {
                foreach($message->getNewChatMembers() as $newMember) {
                    if($newMember->getId() == env('TG_BOT_ID')) {
                        return new AddChat;
                    }
                }
    
            } else if($message->getLeftChatMember()) {
                if($message->getLeftChatMember()->getId() == env('TG_BOT_ID')) {
                    return new DeleteChat;
                }
    
            } else if(is_null($commandMatches)) {
                //ordinary message
            }
        }
    }

    private function getActionForCommand(string $command) {
        if($command == 'start') {
            return new Start;

        } else if($command == 'new') {
            return new Create;

        } else if($command == 'stop') {
            return new Stop;

        } else if($command == 'language') {
            return new Get;

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