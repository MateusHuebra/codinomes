<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    static function createUserFromTGUser(TGChat $tgChat) {
        return self::create([
            'id' => $tgChat->getId(),
            'username' => $tgChat->getUsername(),
            'title' => substr($tgChat->getTitle(), 0, 32),
            'language' => 'pt-br'
        ]);
    }

}
