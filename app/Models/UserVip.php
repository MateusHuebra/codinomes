<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expires_at',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive()
    {
        return $this->expires_at >= now();
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function isExpiredSoon($days = 7)
    {
        return $this->expires_at->diffInDays(now()) <= $days;
    }

    public function renew(int $months = 1)
    {
        $this->expires_at = now()->addMonths($months);
        $this->save();
    }
}
