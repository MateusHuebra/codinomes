<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;
use App\Services\ServerLog;
use TelegramBot\Api\BotApi;
use Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;

class Table {

    static function send(Game $game, BotApi $bot, Caption $caption, int $highlightCard = null, string $winner = null) {
        $chatLanguage = ($game->chat??$game->creator)->language;
        $sendToMasters = ($game->role == 'master' || $winner);
        $cards = self::getCards($game);
        $cardsLeft = CardsLeft::get($cards, $game, $bot);
        $imageGen = ImageGen\Factory::build($game->mode);

        $images = $imageGen->getBaseImages($game, true, $winner);
        $imageGen->addMode($images, $game->mode);
        $imageGen->addCards($images, $cards, $game, $highlightCard);
        $imageGen->addCardsLeft($images, $game, $cardsLeft);
        $imageGen->addCaption($images, $caption);
        $images->makeCURLFilesFromImages();

        if($game->mode == Game::COOP) {
            if($winner) {

            } else {
                self::handleCoopNoWinner($game, $images, $chatLanguage, $bot);
            }
        } else {
            self::deleteCurrentChatMessage($game, $bot);
            if($winner) {
                self::handleWithWinner($game, $images, $winner, $bot);
            } else {
                self::handleNoWinner($game, $images, $sendToMasters, $chatLanguage, $bot);
            }
        }
        
    }

    private static function handleNoWinner(Game $game, Images $images, bool $sendToMasters, string $chatLanguage, BotApi $bot) {
        $keyboard = self::getKeyboard($game, $chatLanguage);
        $text = $game->getPhotoCaption();
        if($game->history !== null) {
            $text.= PHP_EOL.$game->getHistory($game->mode == Game::MYSTERY);
        }
        
        $message = $bot->sendPhoto($game->chat_id, $images->agentsCURLImage, $text, null, $keyboard, false, 'MarkdownV2');
        unlink($images->agentsTempImageFileName);

        $masters = $game->users()->where('role', 'master')->get();
        if($sendToMasters) {
            $text = AppString::getParsed('game.send_hint');
            if($game->history !== null) {
                $text.= PHP_EOL.$game->getHistory();
            }
            try{
                $user = $masters->firstWhere('team', $game->team);
                $masters->forget($masters->search($user));
                self::deleteCurrentUserMessage($user, $bot);
                $user->message_id = $bot->sendPhoto($user->id, $images->masterCURLImage, $text, null, null, false, 'MarkdownV2', null, true)->getMessageId();
                $user->save();
            } catch(Exception $e) {
                $bot->sendMessage($game->chat_id, AppString::get('error.master_not_registered', null, $chatLanguage));
            }
        }

        foreach($masters as $user) {
            try {
                $bot->editMessageMedia($user->id, $user->message_id, new InputMediaPhoto($images->masterCURLImage)); 
            } catch(Exception $e) {
                ServerLog::log($e->getMessage());
            }
        }
        unlink($images->masterTempImageFileName);

        $game->message_id = $message->getMessageId();
        $game->save();
    }
    
    private static function handleCoopNoWinner(Game $game, Images $images, string $chatLanguage, BotApi $bot) {
        $keyboard = self::getKeyboard($game, $chatLanguage);
        $text = $game->getPhotoCaption();
        if($game->history !== null) {
            $text.= PHP_EOL.$game->getHistory($game->mode == Game::MYSTERY);
        }
        
        $bot->sendPhoto($game->creator_id, $images->masterCURLImage, $text, null, $keyboard, false, 'MarkdownV2');
        unlink($images->masterTempImageFileName);
        $bot->sendPhoto($game->getPartner()->id, $images->agentsCURLImage, $text, null, $keyboard, false, 'MarkdownV2');
        unlink($images->agentsTempImageFileName);

        $game->save();
    }

    private static function handleWithWinner(Game $game, Images $images, string $winner, BotApi $bot) {
        $text = Menu::getLobbyText($game, false, $winner);
        $text.= $game->getHistory();

        $bot->sendPhoto($game->chat_id, $images->masterCURLImage, $text, null, null, false, 'MarkdownV2');
        $title = AppString::parseMarkdownV2($game->chat->title)."\n\\".$game->chat_id."\n\n";
        $bot->sendPhoto(env('TG_LOG_ID'), $images->masterCURLImage, $title.$text, null, null, false, 'MarkdownV2');
        unlink($images->masterTempImageFileName);

        $game->stop($bot, $winner);

        UserAchievement::testEndGame($game->users, $bot, $game->chat_id);
    }

    private static function deleteCurrentChatMessage(Game $game, BotApi $bot) {
        if(!is_null($game->message_id)) {
            $bot->tryToDeleteMessage($game->chat_id, $game->message_id);
        }
    }

    private static function deleteCurrentUserMessage(User $user, BotApi $bot) {
        if(!is_null($user->message_id)) {
            $bot->tryToDeleteMessage($user->id, $user->message_id);
        }
    }

    private static function getCards(Game $game) {
        if(in_array($game->mode, [Game::CRAZY, Game::SUPER_CRAZY])) {
            return $game->cards()->get();
        } else {
            return $game->cards;
        }
    }

    private static function getKeyboard(Game $game, string $chatLanguage) {
        if($game->role=='master') {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.open_dm', null, $chatLanguage),
                        'url' => 't.me/CodinomesBot'
                    ]
                ]
            ]);
        } else {
            return new InlineKeyboardMarkup([
                [
                    [
                        'text' => AppString::get('game.skip', null, $chatLanguage),
                        'callback_data' => CDM::toString([
                            CDM::EVENT => CDM::SKIP
                        ])
                    ],
                    [
                        'text' => AppString::get('game.choose_card', null, $chatLanguage),
                        'switch_inline_query_current_chat' => ''
                    ]
                ]
            ]);
        }
    }
    
    private static function getMasterKeyboard(string $chatLanguage) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('game.give_hint', null, $chatLanguage),
                    'switch_inline_query_current_chat' => ''
                ]
            ]
        ]);
    }

}