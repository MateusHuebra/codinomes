<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TelegramBot\Api\Types\User as TGUser;

class User extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'username',
        'name',
        'language',
        'status'
    ];

    static function createFromTGModel(TGUser $tgUser) : User {
        return self::create([
            'id' => $tgUser->getId(),
            'username' => $tgUser->getUsername(),
            'name' => substr($tgUser->getFirstName(), 0, 32),
            'language' => $tgUser->getLanguageCode(),
            'status' => 'actived'
        ]);
    }
}
