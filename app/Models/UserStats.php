<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStats extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'user_id'
    ];

    public static function addAttempt(Game $game, string $team, string $type) {
        $master = $game->users()->fromTeamRole($team, 'master')->first();
        $stats = self::firstOrNew([
            'user_id' => $master->id
        ]);
        $stats->{'hinted_to_'.$type}+= 1;
        $stats->save();

        $agents = $game->users()->fromTeamRole($team, 'agent')->get();
        foreach ($agents as $agent) {
            $stats = self::firstOrNew([
                'user_id' => $agent->id
            ]);
            $stats->{'attempts_on_'.$type}+= 1;
            $stats->save();
        }
    }

}
