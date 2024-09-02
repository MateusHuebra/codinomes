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
        'rbow' => '🌈',
        'cotton' => '🏳️‍⚧️',
        'flower' => '💐',
        'dna' => '🧬',
        'moon' => '🌗',
        'pflag' => '🇧🇷',
        'canary' => '🐤',
        'south' => '🌌',
        'white' => '◽️',
        'black' => '◼️'
    ];

    const CLASSIC = 'classic';
    const FAST = 'fast';
    const MYSTERY = 'mystery';
    const MINESWEEPER = 'mineswp';
    const EIGHTBALL = '8ball';
    const CRAZY = 'crazy';
    const SUPER_CRAZY = 'sp_crazy';
    const EMOJI = 'emoji';
    const TRIPLE = 'triple';
    const COOP = 'coop';

    const MODES = [
        self::CLASSIC => '🪪',
        self::FAST => '⚡️',
        self::MYSTERY => '❔',
        self::MINESWEEPER => '💣',
        self::EIGHTBALL => '🎱',
        self::CRAZY => '🤪',
        self::SUPER_CRAZY => '🤯',
        self::EMOJI => '📲',
        self::TRIPLE => '3️⃣',
        self::COOP => '👥'
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
    private $masterC;
    private $agentsC;
    private $partner;
    private $colors = [];
    private $hasRequiredPlayers = null; 

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('team', 'role')->as('player');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class);
    }

    public function teamColors(): HasMany
    {
        return $this->hasMany(GameTeamColor::class);
    }

    public function getColor(string $team): String
    {
        if(!isset($this->colors[$team])) {
            $this->colors[$team] = $this->hasMany(GameTeamColor::class)
                                        ->where('team', $team)
                                        ->first()
                                        ->color;
        }
        return $this->colors[$team];
    }

    public function getColors(array $colors): Array
    {
        $array = [];
        foreach($colors as $color) {
            $array[] = $this->getColor($color);
        }
        return $array;
    }

    public function setColor(string $team, string $color): int
    {
        $this->colors[$team] = $color;
        return GameTeamColor::where('game_id', $this->id)
                            ->where('team', $team)
                            ->update([
                                'color' => $color
                            ]);
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
            $regex = '/\*['.implode('', self::COLORS).'👥]+ (?<hint>[\w\S\- ]{1,20} [0-9∞]+)\*(\R>  - .+)*$/u';
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

    public function getPartner() {
        if(!isset($this->partner)) {
            $this->partner = $this->users()
                                  ->where('id', '!=', $this->creator_id)
                                  ->first();
        }
        return $this->partner;
    }

    public function getPhotoCaption() {
        $teamColor = $this->getColor($this->team);
        if($this->mode == self::COOP) {
            if($this->attempts_left == 0) {
                return AppString::get('game.turn_sudden_death', null, $this->creator->language);
            }
            switch ($this->role) {
                case 'master':
                    $name = $this->creator->name;
                    $team = Game::COLORS[$teamColor];
                    break;
                case 'agent':
                    $name = $this->getPartner()->name;
                    $team = '👥';
                    break;
                default:
                    return AppString::get('game.coop_waiting', null, $this->creator->language);
            }
            return AppString::get('game.turn_coop', [
                'role' => AppString::parseMarkdownV2($name),
                'team' => $team,
                'players' => ''
            ], $this->creator->language);

        } else {
            $role = $this->role=='master' ? 'game.master' : 'game.agents';
            $playersList = $this->users()->fromTeamRole($this->team, $this->role)->get()->getStringList(true, PHP_EOL);
    
            return AppString::get('game.turn', [
                'role' => AppString::get($role, null, $this->chat->language),
                'team' =>  Game::COLORS[$teamColor],
                'players' => $playersList
            ], $this->chat->language);
        }
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
        if(!in_array($this->status, ['creating', 'lobby'])) {
            return false;
        }

        if($callbackId && (!$user || !$this->hasPermission($user, $bot))) {
            $bot->sendAlertOrMessage($callbackId, $this->chat_id??$this->creator_id, 'error.admin_only');
            return false;
        }

        if(!$this->hasRequiredPlayers()) {
            $callbackId ? $bot->sendAlertOrMessage($callbackId, $this->chat_id??$this->creator_id, 'error.no_required_players') : null;
            if(env('APP_ENV')!='local') {
                return false;
            }
        }

        switch ($this->mode) {
            case self::TRIPLE:
                $firstTeam = array('a', 'b', 'c')[rand(0, 2)];
                break;

            case self::COOP:
                $firstTeam = 'a';
                break;
            
            default:
                $firstTeam = rand(0, 1) ? 'a' : 'b';
                break;
        }
        $isCardsSetted = GameCard::set($this, $firstTeam);
        if(!$isCardsSetted) {
            $callbackId ? $bot->sendAlertOrMessage($callbackId, $this->chat_id??$this->creator_id, 'error.no_enough_cards') : null;
            return false;
        }
        if($this->mode == self::COOP) {
            $this->updateStatus('playing', $firstTeam, null, 9);
        } else {
            $this->updateStatus('playing', $firstTeam, 'master');
            $this->muteMasters($bot);
        }

        $text = Menu::getLobbyText($this) . AppString::getParsed('game.started', null, ($this->chat??$this->creator)->language);
        try {
            $bot->editMessageText($this->chat_id??$this->creator_id, $this->lobby_message_id, $text, 'MarkdownV2');
        } catch(Exception $e) {}

        $caption = new Caption(AppString::get('game.started', null, ($this->chat??$this->creator)->language), null, 50);
        Table::send($this, $bot, $caption);

        return true;
    }

    private function muteMasters(BotApi $bot, bool $allow = false) {
        if(!$this->chat->mute_masters) {
            return;
        }

        $masters = $this->users()->where('role', 'master')->get();
        $untilDate = !$allow ? time()+86400 : null;
        foreach($masters as $master) {
        try{
                $bot->restrictChatMember($this->chat_id, $master->id, $untilDate, $allow, $allow, $allow, $allow);
            } catch(Exception $e) {
                if($e->getMessage() != 'Bad Request: user is an administrator of the chat') {
                    $bot->sendMessage(env('TG_LOG_ID'), 'restrictChatMember: '. $e->getMessage());
                }
            }
        }
    }

    public function stop(BotApi $bot, string $winner = null) {
        if($winner == null) {
            if(in_array($this->status, ['creating', 'lobby'])) {
                $bot->tryToDeleteMessage($this->chat_id??$this->creator_id, $this->lobby_message_id);
            } else {
                $bot->tryToDeleteMessage($this->chat_id??$this->creator_id, $this->message_id);
                $bot->tryToUnpinChatMessage($this->chat_id??$this->creator_id, $this->lobby_message_id);
            }
            $this->status = 'canceled';
        } else {
            $this->status = 'ended';
            $bot->tryToUnpinChatMessage($this->chat_id??$this->creator_id, $this->lobby_message_id);
        }
        
        if($this->mode != self::COOP) {
            UserStats::addGame($this, $winner);
            $this->muteMasters($bot, true);
        }
        
        $this->cards()->delete();

        if($this->status == 'canceled') {
            $this->users()->detach();
            $this->teamColors()->delete();
            parent::delete();
        } else {
            $this->team = $winner;
            $this->role = null;
            $this->attempts_left = null;
            $this->save();
        }
    }

    public function updateStatus(string $status = null, string $team = null, string $role = null, int $attempts_left = null) {
        $this->status = $status??$this->status;
        $this->team = $team??$this->team;
        $this->role = $role??$this->role;
        $this->attempts_left = $attempts_left??$this->attempts_left;
        $this->status_updated_at = date('Y-m-d H-i-s');
        $this->save();
    }

    public function nextStatus(string $nextTeam) {
        if($this->mode == self::CRAZY) {
            GameCard::randomizeUnrevealedCardsColors($this);
        } else if($this->mode == self::SUPER_CRAZY) {
            GameCard::randomizeUnrevealedCardsWords($this);
            GameCard::randomizeUnrevealedCardsColors($this);
        }

        $this->updateStatus('playing', $nextTeam, 'master');
        $this->attempts_left = null;
        $this->save();
    }

    public function nextStatusCoop() {
        $this->updateStatus('playing', null, null, $this->attempts_left-1);
        $this->role = null;
        $this->save();
    }

    public function setEightBallToHistory($player) {
        $color = $this->getColor($player->team == 'a' ? 'b' : 'a');
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
    
    public function getHistory(bool $isMystery = false) {
        if(is_null($this->history)) {
            return null;
        }
        if($isMystery) {
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
            if(
                (
                    !in_array($this->mode, [self::TRIPLE, self::COOP])
                    &&
                    ($this->masterA->count()==0 || $this->agentsA->count()==0 || $this->masterB->count()==0 || $this->agentsB->count()==0)
                )
                ||
                (
                    $this->mode == self::TRIPLE
                    &&
                    ($this->masterA->count()==0 || $this->agentsA->count()==0 || $this->masterB->count()==0 || $this->agentsB->count()==0|| $this->masterC->count()==0 || $this->agentsC->count()==0)
                )
                ||
                (
                    $this->mode == self::COOP
                    &&
                    ($this->masterA->count()==0 || $this->agentsA->count()==0)
                )
            ) {
                $this->hasRequiredPlayers = false;
            } else {
                $this->hasRequiredPlayers = true;
            }
        }
        return $this->hasRequiredPlayers;
    }

    public function getTeamAndPlayersList(string $winner = null) {
        if($this->isTeamAndRolesSet===false) {
            $this->setPlayerTeamAndRoles();
        }

        $empty = '_'.AppString::get('game.empty').'_';
        $teamA = mb_strtoupper(AppString::getParsed('color.'.$this->getColor('a')), 'UTF-8').' '.self::COLORS[$this->getColor('a')];
        $vars = [
            'master_a' => $this->masterA->get()->getStringList()??$empty,
            'agents_a' => $this->agentsA->get()->getStringList()??$empty
        ];

        if($this->mode != self::COOP) {
            $teamB = mb_strtoupper(AppString::getParsed('color.'.$this->getColor('b')), 'UTF-8').' '.self::COLORS[$this->getColor('b')];
            $vars+= [
                'master_b' => $this->masterB->get()->getStringList()??$empty,
                'agents_b' => $this->agentsB->get()->getStringList()??$empty,
                'a' => $teamA . ($winner == 'a' ? ' '.AppString::getParsed('game.won') : ''),
                'b' => $teamB . ($winner == 'b' ? ' '.AppString::getParsed('game.won') : '')
            ];
            $string = 'teams_lists';
        } else {
            switch ($winner) {
                case 'a':
                    $a = ' '.AppString::getParsed('game.won');
                    break;
                case 'x':
                    $a = ' '.AppString::getParsed('game.team_lost');
                    break;
                default:
                    $a = '';
                    break;
            }
            $vars+= ['a' => $teamA . $a];
            $string = 'teams_lists_coop';
        }

        if($this->mode == self::TRIPLE) {
            $teamC = mb_strtoupper(AppString::getParsed('color.'.$this->getColor('c')), 'UTF-8').' '.self::COLORS[$this->getColor('c')];
            $vars+= [
                'master_c' => $this->masterC->get()->getStringList()??$empty,
                'agents_c' => $this->agentsC->get()->getStringList()??$empty,
                'c' => $teamC . ($winner == 'c' ? ' '.AppString::getParsed('game.won') : '')
            ];
            $string = 'teams_lists_triple';
        }
        
        $textMessage = AppString::get('game.'.$string, $vars);
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
        if($this->mode == self::TRIPLE) {
            $this->masterC = $this->users()->fromTeamRole('c', 'master');
            $this->agentsC = $this->users()->fromTeamRole('c', 'agent');
        }
        $this->isTeamAndRolesSet = true;
    }

}
