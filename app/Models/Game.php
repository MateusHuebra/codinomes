<?php

namespace App\Models;

use App\Services\AppString;
use App\Services\Game\Aux\Caption;
use App\Services\Game\Menu;
use App\Services\Game\Table;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Telegram\BotApi;


class Game extends Model
{
    use HasFactory;

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
        'white' => '◽️',
        'black' => '◼️'
    ];

    const MODES = [
        'classic' => '🃏',
        'fast' => '⚡️',
        'mystery' => '❔',
        'mineswp' => '💣',
        '8ball' => '🎱',
        'crazy' => '🤯'
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

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('team', 'role')->as('player');
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
        return $this->chat->hasPermission($user, $bot);
    }

    public function getLastHint() {
        if(!$this->lastHint) {
            $regex = '/\*['.implode('', self::COLORS).']+ (?<hint>[\w\- ]{1,16} [0-9∞]+)\*(\R>  - .+)*$/u';
            if(preg_match($regex, $this->history, $matches)) {
                $this->lastHint = $matches['hint'];
            } else {
                $this->lastHint = '';
            }
        }
        return $this->lastHint;
    }

    public function countLastStreak() {
        preg_match('/(\R>  - .+)*$/', $this->history, $matches);
        return substr_count($matches[0], "\n");
    }

    public function getPhotoCaption() {
        $role = $this->role=='master' ? 'game.master' : 'game.agents';
        $teamColor = $this->{'color_'.$this->team};
        $playersList = $this->users()->fromTeamRole($this->team, $this->role)->get()->getStringList(true, PHP_EOL);

        return AppString::get('game.turn', [
            'role' => AppString::get($role, null, $this->chat->language),
            'team' =>  Game::COLORS[$teamColor],
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
        $this->updateStatus('playing', $firstTeam, 'master');

        $text = Menu::getLobbyText($this) . AppString::getParsed('game.started');
        try {
            $bot->editMessageText($this->chat_id, $this->lobby_message_id, $text, 'MarkdownV2');
        } catch(Exception $e) {}

        $caption = new Caption(AppString::get('game.started'), null, 50);
        Table::send($this, $bot, $caption);

        return true;
    }

    public function stop(BotApi $bot, string $winner = null) {
        if($winner == null) {
            if($this->status == 'creating') {
                $bot->tryToDeleteMessage($this->chat_id, $this->lobby_message_id);
            } else {
                $bot->tryToDeleteMessage($this->chat_id, $this->message_id);
                $bot->tryToUnpinChatMessage($this->chat_id, $this->lobby_message_id);
            }
            $this->status = 'canceled';
        } else {
            $this->status = 'ended';
            $bot->tryToUnpinChatMessage($this->chat_id, $this->lobby_message_id);
        }
        
        UserStats::addGame($this, $winner);
        
        $this->cards()->delete();

        $this->team = $winner;
        $this->role = null;
        $this->attempts_left = null;
        $this->save();

        if($this->status == 'canceled') {
            $this->users()->detach();
            parent::delete();
        }
    }

    public function updateStatus(string $status = null, string $team = null, string $role = null) {
        $this->status = $status??$this->status;
        $this->team = $team??$this->team;
        $this->role = $role??$this->role;
        $this->status_updated_at = date('Y-m-d H-i-s');
        $this->save();
    }

    public function nextStatus(User $user) {
        if($this->mode == 'crazy') {
            GameCard::randomizeUnrevealedCardsColors($this);
        }

        $this->updateStatus('playing', $user->getEnemyTeam(), 'master');
        $this->attempts_left = null;
        $this->save();
    }

    public function setEightBallToHistory($player) {
        $color = ($player->team == 'a') ? $this->color_b : $this->color_a;
        $emoji = self::COLORS[$color];
        $historyLine = $emoji.' '.AppString::get('game.8ball_hint');
        $this->addToHistory('*'.$historyLine.'*');
    }

    public function addToHistory(string $line) {
        if($this->history === null) {
            $this->history = '**>'.$line;
        } else {
            $this->history = $this->history.PHP_EOL.'>'.$line;
        }
        $this->save();
    }
    
    public function getHistory(bool $mystery = false) {
        if(is_null($this->history)) {
            return null;
        }
        if($mystery) {
            $regex = '/ ['.implode('', self::COLORS).']+/u';
            $result = preg_replace($regex, ' ❔', $this->history);
        }
        return str_replace(['.', '-'], ['\.', '\-'], $result??$this->history).'||';
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

    public function hasMaster(string $team) {
        if($this->isTeamAndRolesSet===false) {
            $this->setPlayerTeamAndRoles();
        }

        $master = $team=='a' ? $this->masterA : $this->masterB;
        return $master->exists();
    }

    private function setPlayerTeamAndRoles() {
        $this->masterA = $this->users()->fromTeamRole('a', 'master');
        $this->agentsA = $this->users()->fromTeamRole('a', 'agent');
        $this->masterB = $this->users()->fromTeamRole('b', 'master');
        $this->agentsB = $this->users()->fromTeamRole('b', 'agent');
        $this->isTeamAndRolesSet = true;
    }

}
