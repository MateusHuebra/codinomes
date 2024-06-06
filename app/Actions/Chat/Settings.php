<?php

namespace App\Actions\Chat;

use App\Actions\Action;
use App\Adapters\UpdateTypes\Update;
use App\Models\Chat;
use App\Models\Pack;
use App\Models\User;
use App\Services\AppString;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use App\Services\CallbackDataManager as CDM;

class Settings implements Action {

    public function run(Update $update, BotApi $bot) : Void {
        if(!$update->isChatType('supergroup')) {
            return;
        }
        
        $chat = $update->findChat();
        if(!$chat) {
            $add = new Add();
            $add->run($update, $bot);
            $chat = $update->findChat();
        }

        $user = $update->findUser();
        if(!$user) {
            if($update->isType(Update::CALLBACK_QUERY)) {
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.user_not_registered');
            } else {
                $bot->sendMessage($chat->id, AppString::get('error.user_not_registered'), null, false, $update->getMessageId(), null, false, null, null, true);
            }
            return;
        }

        if(!$chat->hasPermission($user, $bot)) {
            if($update->isType(Update::CALLBACK_QUERY)) {
                $bot->sendAlertOrMessage($update->getCallbackQueryId(), $chat->id, 'error.admin_only');
            } else {
                $bot->sendMessage($chat->id, AppString::get('error.admin_only'), null, false, $update->getMessageId(), null, false, null, null, true);
            }
            return;
        }

        $this->prepareAndSend($update, $bot, $chat, $user);
        
    }

    public function prepareAndSend(Update $update, BotApi $bot, Chat $chat, User $user) {
        if($update->isType(Update::CALLBACK_QUERY)) {
            $data = CDM::toArray($update->getData());
            try {
                $bot->answerCallbackQuery($update->getCallbackQueryId());
            } catch(Exception $e) {}
        }

        if(!isset($data[CDM::MENU]) || $data[CDM::MENU] == 'main') {
            $text = AppString::get('settings.chat');
            $keyboard = self::getKeyboard($chat);

        } else if($data[CDM::MENU] == CDM::PACKS) {
            $cards = $chat->packs->flatMap(function ($pack) {
                return $pack->cards;
            });
            $text = AppString::get('game.packs_count_cards', [
                'count' => $cards->count()
            ]);
            $keyboard = self::getPacksMenu();
        
        } else if($data[CDM::MENU] == CDM::PACKS_ACTIVED) {
            $text = AppString::get('game.packs_actived');
            $packs = $chat->packs();
            $keyboard = self::getPacks($chat, $packs, $data);
        
        } else if($data[CDM::MENU] == CDM::PACKS_OFFICIAL) {
            $text = AppString::get('game.packs_official');
            $packs = Pack::where('user_id', null);
            $keyboard = self::getPacks($chat, $packs, $data);

        } else if($data[CDM::MENU] == CDM::PACKS_USERS) {
            $text = AppString::get('game.packs_users');
            $packs = Pack::where('status', 'public')->whereNotNull('user_id');
            $keyboard = self::getPacks($chat, $packs, $data);
            
        } else if($data[CDM::MENU] == CDM::PACKS_MINE) {
            $text = AppString::get('game.packs_mine');
            $packs = Pack::where('user_id', $user->id);
            $keyboard = self::getPacks($chat, $packs, $data);
        }

        if(isset($data[CDM::MENU])) {
            $bot->editMessageText($chat->id, $update->getMessageId(), $text, null, false, $keyboard);
        } else {
            $bot->sendMessage($chat->id, $text, null, false, $update->getMessageId(), $keyboard, false, null, null, true);
        }
    }

    public static function getKeyboard(Chat $chat) {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => AppString::get('settings.admin_only'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_ADMIN_ONLY,
                        CDM::VALUE => CDM::INFO
                    ])
                ],
                [
                    'text' => AppString::get('settings.'.($chat->admin_only?'on':'off')),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_ADMIN_ONLY
                    ])
                ]
            ],
            [
                [
                    'text' => AppString::get('settings.compound_words'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_COMPOUND_WORDS,
                        CDM::VALUE => CDM::INFO
                    ])
                ],
                [
                    'text' => AppString::get('settings.'.($chat->compound_words?'on':'off')),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_COMPOUND_WORDS
                    ])
                ]
            ],
            [
                [
                    'text' => AppString::get('settings.timer'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_TIMER,
                        CDM::VALUE => CDM::INFO
                    ])
                ],
                [
                    'text' => $chat->timer ? $chat->timer.' '.AppString::get('time.minutes') : AppString::get('settings.off'),
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::CHANGE_TIMER
                    ])
                ]
            ],
            [
                [
                    'text' => 'Gerenciar Pacotes',
                    'callback_data' => CDM::toString([
                        CDM::EVENT => CDM::SETTINGS,
                        CDM::MENU => CDM::PACKS
                    ])
                ]
            ]
        ]);
    }

    private static function getPacksMenu() {
        $buttonsArray = [];
        $buttonsArray[] = [[
            'text' => AppString::get('game.packs_actived'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => CDM::PACKS_ACTIVED,
                CDM::PAGE => 0
            ])
        ]];
        $buttonsArray[] = [[
            'text' => AppString::get('game.packs_official'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => CDM::PACKS_OFFICIAL,
                CDM::PAGE => 0
            ])
        ]];
        $buttonsArray[] = [[
            'text' => AppString::get('game.packs_users'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => CDM::PACKS_USERS,
                CDM::PAGE => 0
            ])
        ]];
        $buttonsArray[] = [[
            'text' => AppString::get('game.packs_mine'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => CDM::PACKS_MINE,
                CDM::PAGE => 0
            ])
        ]];
        $buttonsArray[] = [[
            'text' => AppString::get('game.back'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => 'main',
            ])
        ]];
        return new InlineKeyboardMarkup($buttonsArray);
    }

    private static function getPacks(Chat $chat, $packs, $data) {
        $chatPacks = $chat->packs;
        $take = 5;
        $skip = ($data[CDM::PAGE]) * $take;
        $packs = $packs->orderBy('id', 'asc')->skip($skip)->take($take);
        $totalPages = $packs->count() / $take;

        foreach ($packs->get() as $pack) {
            if($chatPacks->find($pack->id)) {
                $text = '• '.$pack->name.' ('.$pack->cards()->count().')';
                $bool = 0;
            } else {
                $text = $pack->name.' ('.$pack->cards()->count().')';
                $bool = 1;
            }
            $buttonsArray[] = [[
                'text' => $text,
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::CHANGE_PACK,
                    CDM::TEXT => $pack->id,
                    CDM::NUMBER => $bool,
                    CDM::MENU => $data[CDM::MENU],
                    CDM::PAGE => $data[CDM::PAGE]
            ])
        ]];
        }

        $line = [];
        if($data[CDM::PAGE]>0) {
            $line[] = [
                'text' => '<',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SETTINGS,
                    CDM::MENU => $data[CDM::MENU],
                    CDM::PAGE => $data[CDM::PAGE]-1
                ])
            ];
        }
        $line[] = [
            'text' => AppString::get('game.back'),
            'callback_data' => CDM::toString([
                CDM::EVENT => CDM::SETTINGS,
                CDM::MENU => CDM::PACKS,
            ])
        ];
        if($data[CDM::PAGE]<$totalPages-1) {
            $line[] = [
                'text' => '>',
                'callback_data' => CDM::toString([
                    CDM::EVENT => CDM::SETTINGS,
                    CDM::MENU => $data[CDM::MENU],
                    CDM::PAGE => $data[CDM::PAGE]+1,
                ])
            ];
        }
        $buttonsArray[] = $line;
        
        return new InlineKeyboardMarkup($buttonsArray);
    }

}