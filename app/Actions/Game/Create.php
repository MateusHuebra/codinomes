<?php

namespace App\Actions\Game;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Game;
use App\Models\GlobalSettings;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Create implements Action {

    private $mode;

    public function __construct(string $mode = Game::CLASSIC) {
        $this->mode = $mode;
    }

    public function run(Update $update, BotApi $bot) : Void {
        if(
            (GlobalSettings::first()->official_groups_only || $this->mode == Game::COOP)
            &&
            (
                !in_array($update->getChatId(), explode(',', env('TG_OFICIAL_GROUPS_IDS')))
                &&
                $update->getChatId() != env('TG_MY_ID')
            )
        ) {
            $bot->sendMessage($update->getChatId(), AppString::get('error.only_oficial_groups'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($update->isChatType('supergroup')) {
            $game = $this->groupGame($update, $bot);

        } else if($update->isChatType('private')) {
            $game = $this->privateGame($update, $bot);
        
        } else {
            return;
        }

        if($game) {
            Menu::send($game, $bot);
        }
    }

    private function groupGame(Update $update, BotApi $bot) {
        if($this->mode == Game::COOP) {
            return;
        }

        $chat = $update->findChat();
        $chat->username = $update->getChatUsername();
        $chat->actived = true;
        $chat->title = mb_substr($update->getChatTitle(), 0, 32, 'UTF-8');
        $chat->save();

        $user = $update->findUser();
        if(!$user || !$chat) {
            $bot->sendMessage($chat->id, AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if($chat->currentGame()) {
            $bot->sendMessage($chat->id, AppString::get('game.already_exists'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($this->mode == 'random') {
            while($this->mode == 'random' || $this->mode == Game::COOP) {
                $this->mode = array_rand(Game::MODES);
            }
        }

        $game = new Game();
        $game->status = 'creating';
        $game->mode = $this->mode;
        $game->chat_id = $chat->id;
        $game->creator_id = $user->id;
        $game->save();

        $this->setGameColors($game);

        if($game->chat->packs()->count() == 0) {
            $bot->sendMessage($chat->id, AppString::get('error.no_packs'));
        }

        return $game;
    }

    private function privateGame(Update $update, BotApi $bot) {
        if($this->mode != Game::COOP) {
            return;
        }

        $user = $update->findUser();
        if(!$user) {
            $bot->sendMessage($user->id, AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }
        if($user->currentGame()) {
            $bot->sendMessage($user->id, AppString::get('game.already_exists'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        $game = new Game();
        $game->status = 'lobby';
        $game->mode = $this->mode;
        $game->creator_id = $user->id;
        $game->save();

        $this->setGameColors($game);

        $user->games()->syncWithoutDetaching([
            $game->id => [
                'team' => 'a',
                'role' => 'master'
            ]
        ]);

        return $game;
    }

    private function setGameColors(Game $game) {
        $primaryColor = $game->mode==Game::COOP ? 'purple' : 'red';
        $game->teamColors()->create([
            'team' => 'a',
            'color' => $primaryColor
        ]);

        if($game->mode != Game::COOP) {
            $game->teamColors()->create([
                'team' => 'b',
                'color' => 'blue'
            ]);
        }

        if($game->mode == Game::TRIPLE) {
            $game->teamColors()->create([
                'team' => 'c',
                'color' => 'orange'
            ]);
        }
    }

}