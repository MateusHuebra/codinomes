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

    public function __construct(string $mode = 'classic') {
        $this->mode = $mode;
    }

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
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
        if(
            (GlobalSettings::first()->official_groups_only || $this->mode == 'emoji')
            &&
            !in_array($chat->id, explode(',', env('TG_OFICIAL_GROUPS_IDS')))
        ) {
            $bot->sendMessage($chat->id, AppString::get('error.only_oficial_groups'), null, false, $update->getMessageId(), null, false, null, null, true);
            return;
        }

        if($this->mode == 'random') {
            while($this->mode == 'random' || $this->mode == 'emoji') {
                $this->mode = array_rand(Game::MODES);
            }
        }

        $game = new Game();
        $game->status = 'creating';
        $game->mode = $this->mode;
        $game->chat_id = $chat->id;
        $game->creator_id = $user->id;
        $game->save();

        $game->teamColors()->create([
            'team' => 'a',
            'color' => 'red'
        ]);
        $game->teamColors()->create([
            'team' => 'b',
            'color' => 'blue'
        ]);
        if($game->mode == 'triple') {
            $game->teamColors()->create([
                'team' => 'c',
                'color' => 'orange'
            ]);
        }

        Menu::send($game, $bot);

        if($game->chat->packs()->count() == 0) {
            $bot->sendMessage($chat->id, AppString::get('error.no_packs'));
        }
    }

}