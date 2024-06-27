<?php

namespace App\UpdateHandlers;

use App\Actions\Achievements;
use App\Actions\Chat\Add as AddChat;
use App\Actions\Chat\Delete as DeleteChat;
use App\Actions\Chat\Settings;
use App\Actions\Game\ChosenGuess;
use App\Actions\Game\ChosenHint;
use App\Actions\Game\Info;
use App\Actions\Game\Leave;
use App\Actions\Game\SendList;
use App\Actions\Game\Table;
use App\Actions\GetColors;
use App\Actions\Game\ConfirmSkip;
use App\Actions\Game\Create;
use App\Actions\Game\History;
use App\Actions\Game\Stop;
use App\Actions\Help;
use App\Actions\Language\Get;
use App\Actions\LookingForAGame;
use App\Actions\Notify;
use App\Actions\OfficialGroupOnly;
use App\Actions\Pack\WebApp;
use App\Actions\Ping;
use App\Actions\ReactToMessage;
use App\Actions\Start;
use App\Actions\Stats;
use App\Actions\Test;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Message as MessageType;

class Message implements UpdateHandler {

    const LOOKING_FOR_A_GAME = '/(cad[eê]|vamos?|quero|bora|puxa) ((o|a) )?(codinomes?|codenames?|jogar?|jogo|codis?|codes?|((uma? )?(partida|jogo|game)))/i';

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

        if($update->isChatType('supergroup')) {
            if(
                !in_array($update->getChatId(), explode(',', env('TG_OFICIAL_GROUPS_IDS')))
                &&
                preg_match(self::LOOKING_FOR_A_GAME, $update->getMessageText())
            ) {
                return new LookingForAGame;
            }

            if($update->getViaBot()) {
                if(str_contains($update->getViaBotUsername(), 'last')) {
                    return new ReactToMessage('🔥');

                } else if(str_contains($update->getViaBotUsername(), 'ww') || str_contains($update->getViaBotUsername(), 'werewolf')) {
                    return new ReactToMessage('🤮');
                }
            }

            if(str_contains($update->getMessageText(), 'TermogramBot')) {
                return new ReactToMessage('❤️');
            }
        }
        
        return null;
    }

    private function getActionForCommand(string $command) {
        if($command == 'start') {
            return new Start;

        } else if(in_array($command, ['new', 'novo'])) {
            return new Create;

        } else if(in_array($command, ['new_triple', 'novo_triplo'])) {
            return new Create('triple');

        } else if(in_array($command, ['new_fast', 'novo_rapido'])) {
            return new Create('fast');

        } else if(in_array($command, ['new_minesweeper', 'novo_campominado'])) {
            return new Create('mineswp');

        } else if(in_array($command, ['new_mystery', 'novo_fantasma'])) {
            return new Create('mystery');

        } else if(in_array($command, ['new_eightball', 'novo_bilhar'])) {
            return new Create('8ball');

        } else if(in_array($command, ['new_crazy', 'novo_maluco'])) {
            return new Create('crazy');

        } else if(in_array($command, ['new_supercrazy', 'novo_supermaluco'])) {
            return new Create('sp_crazy');

        } else if(in_array($command, ['new_emoji', 'novo_emoji'])) {
            return new Create('emoji');

        } else if(in_array($command, ['new_coop', 'novo_coop'])) {
            return new Create('coop');

        } else if(in_array($command, ['new_random', 'novo_aleatorio'])) {
            return new Create('random');

        } else if(in_array($command, ['table', 'tabela'])) {
            return new Table;

        } else if(in_array($command, ['list', 'listar'])) {
            return new SendList;

        } else if(in_array($command, ['stop', 'parar'])) {
            return new Stop;

        } else if(in_array($command, ['leave', 'sair'])) {
            return new Leave;

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

        } else if(in_array($command, ['help_modes', 'ajuda_modos'])) {
            return new Help('modes');

        } else if(in_array($command, ['packs', 'pacotes'])) {
            return new WebApp;

        } else if(in_array($command, ['achievements', 'conquistas'])) {
            return new Achievements;

        } else if($command == 'test') {
            return new Test;

        } else if($command == 'off') {
            return new OfficialGroupOnly;

        } else if($command == 'info') {
            return new Info;

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