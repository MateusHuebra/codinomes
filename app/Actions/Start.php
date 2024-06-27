<?php

namespace App\Actions;

use App\Adapters\UpdateTypes\Update;
use App\Actions\Language\Get as GetLanguage;
use App\Models\Chat;
use App\Models\Game;
use App\Models\User;
use App\Services\Game\Menu;
use TelegramBot\Api\BotApi;
use App\Services\AppString;

class Start implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if($update->isChatType('private')) {
            if($user = $this->checkIfUserOrChatExists($update->getFrom(), User::class, $bot, 'start.welcome')) {
                if(!$this->startCoop($update->getMessageText(), $user, $update, $bot)) {
                    $bot->sendMessage($user->id, AppString::get('start.questions'), null, false, $update->getMessageId(), null, false, null, null, true);
                }
            }
            
        } else if($update->isChatType('supergroup')) {
            if($chat = $this->checkIfUserOrChatExists($update->getChat(), Chat::class, $bot, 'language.choose_chat')) {
                if($chat->currentGame() && $user = $update->findUser()) {
                    $chat->currentGame()->start($bot, $user, 1);
                } else {
                    $bot->sendMessage($chat->id, AppString::get('start.questions'), null, false, $update->getMessageId(), null, false, null, null, true);
                }
            }

        } else {
            $bot->sendMessage($update->getChatId(), AppString::get('error.must_be_supergroup'));
        }
    }

    private function checkIfUserOrChatExists($tgModel, string $modelClass, BotApi $bot, string $stringPath) {
        $model = $modelClass::find($tgModel->getId());
        if(!$model) {
            $model = $modelClass::createFromTGModel($tgModel);
            $keyboard = GetLanguage::getKeyboard(true);
            $bot->sendMessage($model->id, AppString::get($stringPath), null, false, null, $keyboard);
            return false;
        }
        if(get_class($model) == 'App\Models\User') {
            $model->status = 'actived';
        } else if(get_class($model) == 'App\Models\Chat') {
            $model->actived = true;
        }
        $model->save();
        return $model;
        
    }

    private function startCoop(string $text, User $user, Update $update, BotApi $bot) {
        if(preg_match('/\/start coop_(?<user>[0-9]+)_(?<game>[0-9]+)/u', $text, $matches)) {
            $game = Game::where('creator_id', $matches['user'])
                        ->where('id', $matches['game'])
                        ->where('status', 'lobby')
                        ->first();
            if(!$game || $user->currentGame()) {
                return;
            }
            if($game->users()->fromTeamRole('a', 'agent')->exists()) {
                return;
            }

            $user->games()->syncWithoutDetaching([
                $game->id => [
                    'team' => 'a',
                    'role' => 'agent'
                ]
            ]);

            $user->name = mb_substr($update->getFrom()->getFirstName(), 0, 32, 'UTF-8');
            $user->username = $update->getFrom()->getUsername();
            $user->save();

            Menu::send($game, $bot, true);
            $bot->sendMessage($user->id, AppString::get('game.invite_coop_accepted', [
                'name' => AppString::get('game.mention', [
                    'id' => $game->creator_id,
                    'name' => $game->creator->name
                ])
            ]), 'MarkdownV2');
            return true;
        }
    }

}