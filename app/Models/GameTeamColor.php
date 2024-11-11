<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTeamColor extends Model
{

    const COLORS = [
        'red' => '🔴',
        'blue' => '🔷',
        'pink' => '🩷',
        'orange' => '🔶',
        'purple' => '🟣',
        'green' => '♻️',
        'yellow' => '⭐️',
        'gray' => '🩶',
        'brown' => '🍪',
        'cyan' => '🧩',
        'rbow' => '🌈',
        'cotton' => '🏳️‍⚧️',
        'flower' => '💐',
        'dna' => '🧬',
        'moon' => '🌗',
        'pflag' => '🇧🇷',
        'canary' => '🐤',
        'south' => '🌌',
        'jacko' => '🎃',
        'web' => '🕸',
        'bat' => '🦇',
        'snow' => '❄️',
        'tree' => '🎄',
        'bonnet' => '🎅',
        'white' => '◽️',
        'black' => '◼️'
    ];

    const OFF = ['white', 'black'];
    const BASE = ['red', 'blue', 'pink', 'orange', 'purple', 'green', 'yellow', 'gray', 'brown', 'cyan'];

    const JUNE = ['rbow', 'cotton', 'flower', 'dna', 'moon'];
    const SEPTEMBER = ['pflag', 'canary', 'south'];
    const OCTOBER = ['jacko', 'web', 'bat'];
    const DECEMBER = ['snow', 'tree','bonnet'];

    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'game_id',
        'team',
        'color'
    ];

    public static function getAvailableColors(bool $isVip = false, bool $ignoreExtraColors = false) {
        if($isVip) {
            return [...self::BASE, ...self::JUNE, ...self::SEPTEMBER, ...self::OCTOBER, ...self::DECEMBER];
        }
        
        return self::getNonVipAvailableColors($ignoreExtraColors);
    }

    private static function getNonVipAvailableColors(bool $ignoreExtraColors = false) {
        $colors = GameTeamColor::BASE;    
        $monthConst = 'App\Models\GameTeamColor::'.strtoupper(date('F'));
        if(!$ignoreExtraColors && defined($monthConst)) {
            $colors = [...$colors, ...constant($monthConst)];
        }
        return $colors;
    }

    public static function isColorAllowedToUser(User $user, string $color, bool $ignoreExtraColors = false) {
        if($user->isVip()) {
            return true;
        }
        return in_array($color, self::getNonVipAvailableColors($ignoreExtraColors));
    }
    
}
