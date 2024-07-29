<?php

namespace App\Services\Game;

use App\Models\Game;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Aux\CardsLeft;
use App\Services\Game\Aux\Images;
use TelegramBot\Api\BotApi;
use Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;

class Table {

    static function send(Game $game, BotApi $bot, Caption $caption, int $highlightCard = null, string $winner = null) {
        $chatLanguage = ($game->chat??$game->creator)->language;
        $sendToMasters = ($game->role == 'master' || $winner);
        $cards = self::getCards($game);
        $cardsLeft = CardsLeft::get($cards, $game, $bot);
        $imageGen = ImageGen\Factory::build($game->mode);

        $images = $imageGen->getBaseImages($game, $sendToMasters, $winner);
        $imageGen->addMode($images, $game->mode);
        $imageGen->addCards($images, $cards, $game, $highlightCard);
        $imageGen->addCardsLeft($images, $game, $cardsLeft);
        $imageGen->addCaption($images, $caption);
        $images->makeCURLFilesFromImages();

        if($game->mode == Game::COOP) {
            if($winner) {
                self::handleCoopWithWinner($game, $images, $winner, $bot);
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
        
        $game->message_id = $bot->sendPhoto($game->chat_id, $images->agentsCURLImage, $text, null, $keyboard, false, 'MarkdownV2')->getMessageId();
        $game->save();
        unlink($images->agentsTempImageFileName);

        if($sendToMasters) {
            $user = $game->users()->fromTeamRole($game->team, 'master')->first();
            $oldUserMessageId = $user->message_id;
            $text = AppString::getParsed('game.send_hint');
            if($game->history !== null) {
                $text.= PHP_EOL.$game->getHistory();
            }
            try{
                $user->message_id = $bot->sendPhoto($user->id, $images->masterCURLImage, $text, null, null, false, 'MarkdownV2', null, true)->getMessageId();
                $user->save();
                unlink($images->masterTempImageFileName);
            } catch(Exception $e) {
                $bot->sendMessage($game->chat_id, AppString::get('error.master_not_registered', null, $chatLanguage));
            }
            self::deleteCurrentUserMessage($user->id, $bot, $oldUserMessageId);
        }

    }
    
    private static function handleCoopNoWinner(Game $game, Images $images, string $chatLanguage, BotApi $bot) {
        $keyboards = self::getCoopKeyboard($game, $chatLanguage);
        $text = $game->getPhotoCaption();
        if($game->history !== null) {
            $text.= PHP_EOL.$game->getHistory($game->mode == Game::MYSTERY);
        }

        $creator = User::find($game->creator_id);
        $partner = $game->getPartner();
        
        $creatorMessageId = $bot->sendPhoto($creator->id, $images->masterCURLImage, $text, null, $keyboards['creator'], false, 'MarkdownV2')->getMessageId();
        unlink($images->masterTempImageFileName);
        $partnerMessageId = $bot->sendPhoto($partner->id, $images->agentsCURLImage, $text, null, $keyboards['partner'], false, 'MarkdownV2')->getMessageId();
        unlink($images->agentsTempImageFileName);

        self::deleteCurrentUserMessage($creator->id, $bot, $creator->message_id);
        self::deleteCurrentUserMessage($partner->id, $bot, $partner->message_id);
        
        $creator->message_id = $creatorMessageId;
        $partner->message_id = $partnerMessageId;
        $creator->save();
        $partner->save();

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

    private static function handleCoopWithWinner(Game $game, Images $images, string $winner, BotApi $bot) {
        $text = Menu::getLobbyText($game, false, $winner);
        $text.= $game->getHistory();

        $creator = User::find($game->creator_id);
        $partner = $game->getPartner();

        $media = new ArrayOfInputMedia();
        $media->addItem(new InputMediaPhoto('attach://master', $text, 'MarkdownV2'));
        $media->addItem(new InputMediaPhoto('attach://agents'));
        $attachments = [
            'master' => $images->masterCURLImage,
            'agents' => $images->agentsCURLImage
        ];
        
        $bot->sendMediaGroup($creator->id, $media, false, null, null, null, null, $attachments);
        $bot->sendMediaGroup($partner->id, $media, false, null, null, null, null, $attachments);
        $bot->sendMediaGroup(env('TG_LOG_ID'), $media, false, null, null, null, null, $attachments);
        unlink($images->masterTempImageFileName);

        $game->stop($bot, $winner);

        $bot->sendMessage($creator->id, AppString::get('game.dm_stop', null, $creator->language));
        $bot->sendMessage($partner->id, AppString::get('game.dm_stop', null, $partner->language));

        self::deleteCurrentUserMessage($creator->id, $bot, $creator->message_id);
        self::deleteCurrentUserMessage($partner->id, $bot, $partner->message_id);
    }

    private static function deleteCurrentChatMessage(Game $game, BotApi $bot) {
        if(!is_null($game->message_id)) {
            $bot->tryToDeleteMessage($game->chat_id, $game->message_id);
        }
    }

    private static function deleteCurrentUserMessage(int $userId, BotApi $bot, int $messageId = null) {
        if(!is_null($messageId)) {
            $bot->tryToDeleteMessage($userId, $messageId);
        }
    }

    private static function getCards(Game $game) {
        if(in_array($game->mode, [Game::CRAZY, Game::SUPER_CRAZY])) {
            return $game->cards()->get();
        } else {
            return $game->cards;
        }
    }    

    private static function getCoopKeyboard(Game $game, string $chatLanguage) {
        if($game->role == 'master' || $game->attempts_left == 0) {
            $creator = self::getKeyboardAgentTurn($chatLanguage);
        } else {
            $creator = null;
        }
        
        if($game->role == 'agent' || $game->attempts_left == 0) {
            $partner = self::getKeyboardAgentTurn($chatLanguage);
        } else {
            $partner = null;
        }

        return [
            'creator' => $creator,
            'partner' => $partner
        ];
    }

    private static function getKeyboard(Game $game, string $chatLanguage) {
        if($game->role=='master') {
            return self::getKeyboardMasterTurn($chatLanguage);
        } else {
            return self::getKeyboardAgentTurn($chatLanguage);
        }
    }

    private static function getKeyboardMasterTurn(string $chatLanguage) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('game.open_dm', null, $chatLanguage),
                    'url' => 't.me/CodinomesBot'
                ]
            ]
        ]);
    }

    private static function getKeyboardAgentTurn(string $chatLanguage) {
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