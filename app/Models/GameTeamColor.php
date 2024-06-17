<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTeamColor extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'game_id',
        'team',
        'color'
    ];
}
