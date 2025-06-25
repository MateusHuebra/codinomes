<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'shortname',
        'start_at',
        'end_at',
    ];

    public $timestamps = false;

    public function teamColors()
    {
        return $this->hasMany(TeamColor::class);
    }

    public function hasTeamColors()
    {
        return $this->teamColors()->exists();
    }
}
