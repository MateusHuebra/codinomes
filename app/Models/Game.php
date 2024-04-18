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
    public $auxMenu = null;
    public $auxSubMenu = null;
    public $auxMenuPage = 0;

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

    public function isMenu(String $menu, String $subMenu = null) {
        return ($menu == $this->getMenu() && $subMenu == $this->auxSubMenu);
    }

    public function getMenu(bool $withSubMenu = false) {
        if(!$this->menu) {
            return null;
        }
        if(!$this->auxMenu) {
            preg_match('/^(?<menu>[a-z]+)_?(?<sub>[a-z]*):?(?<page>[0-9]*)$/', $this->menu, $matches);
            $this->auxMenu = $matches['menu'];
            $this->auxSubMenu = $matches['sub'];
            $this->auxMenuPage = $matches['page'];
            if(strlen($this->auxMenuPage)==0) {
                $this->auxMenuPage = 0;
            }
        }
        $menu = $this->auxMenu;
        if($withSubMenu) {
            $menu.='_'.$this->auxSubMenu;
        }
        return $menu;
    }
    
    public function getMenuPage() {
        $this->getMenu();
        return $this->auxMenuPage;
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
