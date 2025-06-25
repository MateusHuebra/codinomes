<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'badge_shortname',
    ];

    const AVAILABLE = [
        'dev',
        'mrs_dev',
        'admin',
        'artist',
    ];

    const EMOJIS = [
        'dev' => '👨‍💻',
        'mrs_dev' => '❤️',
        'admin' => '👮',
        'artist' => '🎨',
    ];
}
