<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TelegramBot\Api\BotApi;


class Game extends Model
{
    use HasFactory;

    const COLORS = [
        'purple' => '🟣',
        'orange' => '🟠',
        'red' => '🔴',
        'blue' => '🔵',
        'green' => '🟢',
        'yellow' => '🟡'
    ];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function hasPermission(User $user, BotApi $bot) {
        if($user->id === $this->creator->id) {
            return true;
        }
        return $this->chat->isAdmin($user, $bot);
    }

    public function stop() {
        foreach($this->users as $user) {
            $user->leaveGame();
        }
        foreach($this->cards as $card) {
            $card->delete();
        }
        $this->delete();
    }

    public function updateStatus(string $status) {
        $this->status = $status;
        $this->status_updated_at = date('Y-m-d H-i-s');
        $this->save();
    }

    public function nextStatus(User $user) {
        $nextStatus = 'master_'.$user->getEnemyTeam();
        $this->updateStatus($nextStatus);
        $this->attempts_left = null;
        $this->save();
    }

    public function addToHistory(string $line) {
        $this->history = $this->history.PHP_EOL.$line;
        $this->save();
    }

}
