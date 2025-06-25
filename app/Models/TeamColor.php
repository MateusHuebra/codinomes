<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'shortname',
        'emoji',
        'is_free',
        'event_id',
        'creator_id',
    ];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function hasEvent()
    {
        return $this->event()->exists();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function hasCreator()
    {
        return $this->creator()->exists();
    }

    public function isFree()
    {
        return $this->is_free;
    }

    public function isPaid()
    {
        return !$this->is_free;
    }

    public function isCreatedBy(User $user)
    {
        return $this->creator_id === $user->id;
    }
}
