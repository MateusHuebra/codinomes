<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Chat as TGChat;
use TelegramBot\Api\Types\User;

class Chat extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'username',
        'title',
        'language'
    ];

    public function isTgUserAdmin(User $tgUser, BotApi $bot) : bool {
        $admins = $bot->getChatAdministrators($this->id);
        foreach ($admins as $admin) {
            if($admin->getUser()->getId() == $tgUser->getId()) {
                return true;
            }
        }
        return false;
    }

    static function createFromTGModel(TGChat $tgChat) : Chat {
        return self::create([
            'id' => $tgChat->getId(),
            'username' => $tgChat->getUsername(),
            'title' => substr($tgChat->getTitle(), 0, 32),
            'language' => 'pt-br'
        ]);
    }

}
