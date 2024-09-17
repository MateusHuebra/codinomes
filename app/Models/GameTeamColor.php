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
        'white' => '◽️',
        'black' => '◼️'
    ];

    const OFF = ['white', 'black'];
    const BASE = ['red', 'blue', 'pink', 'orange', 'purple', 'green', 'yellow', 'gray', 'brown', 'cyan'];

    const JUNE = ['rbow', 'cotton', 'flower', 'dna', 'moon'];
    const SEPTEMBER = ['pflag', 'canary', 'south'];
    const OCTOBER = ['jacko', 'web', 'bat'];

    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'game_id',
        'team',
        'color'
    ];
    
}
