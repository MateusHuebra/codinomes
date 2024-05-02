<?php

namespace App\Models;

use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Table;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TelegramBot\Api\BotApi;


class Game extends Model
{
    use HasFactory;

    const COLORS = [
        'red' => '♦️',
        'purple' => '👾',
        'orange' => '🍂',
        'green' => '🍀',
        'blue' => '🌀',
        'pink' => '💕',
        'yellow' => '🔆',
        'brown' => '🧸',
        'cyan' => '📗',
        'white' => '⚪️',
        'black' => '⚫️'
    ];

    public $timestamps = false;
    public $auxMenu = null;
    public $auxSubMenu = null;
    public $auxMenuPage = 0;
    public $lastHint = null;

    private $isTeamAndRolesSet = false;
    private $masterA;
    private $agentsA;
    private $masterB;
    private $agentsB;
    private $hasRequiredPlayers = null; 

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

    public function getLastHint() {
        if(!$this->lastHint) {
            preg_match('/[*].+ (?<hint>[A-ZÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ]{1,16} [0-9∞]+)[*](\R>.+)*$/', $this->history, $matches);
            $this->lastHint = $matches['hint'];
        }
        return $this->lastHint;
    }

    public function getPhotoCaption() {
        switch ($this->status) {
            case 'master_a':
                $role = 'game.master';
                $teamColor = 'color_a';
                $playersList = $this->users()->fromTeamRole('a', 'master')->get()->getStringList(true, PHP_EOL);
                break;
            case 'agent_a':
                $role = 'game.agents';
                $teamColor = 'color_a';
                $playersList = $this->users()->fromTeamRole('a', 'agent')->get()->getStringList(true, PHP_EOL);
                break;
            case 'master_b':
                $role = 'game.master';
                $teamColor = 'color_b';
                $playersList = $this->users()->fromTeamRole('b', 'master')->get()->getStringList(true, PHP_EOL);
                break;
            case 'agent_b':
                $role = 'game.agents';
                $teamColor = 'color_b';
                $playersList = $this->users()->fromTeamRole('b', 'agent')->get()->getStringList(true, PHP_EOL);
                break;
        }

        return AppString::get('game.turn', [
            'role' => AppString::get($role, null, $this->chat->language),
            'team' =>  Game::COLORS[$this->$teamColor],
            'players' => $playersList
        ], $this->chat->language);
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

    public function start(BotApi $bot, User $user = null, $callbackId = null) : Bool {
        if($this->status != 'creating') {
            $bot->deleteMessage($this->chat_id, $this->message_id);
            return false;
        }

        if($callbackId && (!$user || !$this->hasPermission($user, $bot))) {
            $bot->sendAlertOrMessage($callbackId, $this->chat_id, 'error.admin_only');
            return false;
        }

        if(!$this->hasRequiredPlayers()) {
            $callbackId ? $bot->sendAlertOrMessage($callbackId, $this->chat_id, 'error.no_required_players') : null;
            if(env('APP_ENV')!='local') {
                return false;
            }
        }

        $firstTeam = rand(0, 1) ? 'a' : 'b';
        $isCardsSetted = GameCard::set($this, $firstTeam);
        if(!$isCardsSetted) {
            $callbackId ? $bot->sendAlertOrMessage($callbackId, $this->chat_id, 'error.no_enough_cards') : null;
            return false;
        }
        $this->updateStatus('master_'.$firstTeam);  

        $messageId = $this->message_id;
        $this->message_id = null;

        $text = $this->getTeamAndPlayersList().AppString::getParsed('game.started');
        try {
            $bot->editMessageText($this->chat_id, $messageId, $text, 'MarkdownV2');
            $bot->unpinChatMessage($this->chat_id, $messageId);
        } catch(Exception $e) {}

        $caption = new Caption(AppString::get('game.started'), null, 50);
        Table::send($this, $bot, $caption, null, null, true);

        return true;
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

    public function hasRequiredPlayers() : Bool {
        if($this->isTeamAndRolesSet === false) {
            $this->setPlayerTeamAndRoles();
        }
        if($this->hasRequiredPlayers === null) {
            if($this->masterA->count()==0 || $this->agentsA->count()==0 || $this->masterB->count()==0 || $this->agentsB->count()==0) {
                $this->hasRequiredPlayers = false;
            } else {
                $this->hasRequiredPlayers = true;
            }
        }
        return $this->hasRequiredPlayers;
    }

    public function getTeamAndPlayersList(bool $mention = true) {
        if($this->isTeamAndRolesSet===false) {
            $this->setPlayerTeamAndRoles();
        }

        $teamA = mb_strtoupper(AppString::get('color.'.$this->color_a), 'UTF-8').' '.self::COLORS[$this->color_a];
        $teamB = mb_strtoupper(AppString::get('color.'.$this->color_b), 'UTF-8').' '.self::COLORS[$this->color_b];
        $empty = '_'.AppString::get('game.empty').'_';
        $textMessage = AppString::get('game.teams_lists', [
            'master_a' => $this->masterA->get()->getStringList($mention)??$empty,
            'agents_a' => $this->agentsA->get()->getStringList($mention)??$empty,
            'master_b' => $this->masterB->get()->getStringList($mention)??$empty,
            'agents_b' => $this->agentsB->get()->getStringList($mention)??$empty,
            'a' => $teamA,
            'b' => $teamB
        ]);
        return $textMessage;
    }

    private function setPlayerTeamAndRoles() {
        $this->masterA = $this->users()->fromTeamRole('a', 'master');
        $this->agentsA = $this->users()->fromTeamRole('a', 'agent');
        $this->masterB = $this->users()->fromTeamRole('b', 'master');
        $this->agentsB = $this->users()->fromTeamRole('b', 'agent');
        $this->isTeamAndRolesSet = true;
    }

}
