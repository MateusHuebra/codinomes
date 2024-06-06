<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Chat as TGChat;

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

    public function games(): HasMany
    {
        return $this->HasMany(Game::class);
    }

    public function currentGame()
    {
        return $this->hasMany(Game::class)->whereIn('status', ['creating', 'playing'])->first();
    }

    public function packs()
    {
        return $this->belongsToMany(Pack::class);
    }

    public function notifiableUsers()
    {
        return $this->belongsToMany(User::class, 'notifiable_chat_users')->withPivot('message_id');
    }

    public function hasPermission(User $user, BotApi $bot) {
        if($this->admin_only == false) {
            return true;
        }
        return $this->isAdmin($user, $bot);
    }
    
    public function isAdmin(User $user, BotApi $bot) : bool {
        $admins = $bot->getChatAdministrators($this->id);
        foreach ($admins as $admin) {
            if($admin->getUser()->getId() == $user->id) {
                return true;
            }
        }
        return false;
    }

    public function getUrl() {
        return str_replace('-100', 'https://t.me/c/', $this->id);
    }

    static function createFromTGModel(TGChat $tgChat) : Chat {
        return self::create([
            'id' => $tgChat->getId(),
            'username' => $tgChat->getUsername(),
            'title' => mb_substr($tgChat->getTitle(), 0, 32, 'UTF-8'),
            'language' => 'pt-br'
        ]);
    }

}
