<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CallbackDataManager as CDM;

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

    public static function addColorsToKeyboard(User|null $user = null, array $buttonsArray = [], string $event = CDM::CHANGE_COLOR) {
        $line = [];
        $i = 0;

        $colors = self::getAvailableColors($user, $event === CDM::CHANGE_DEFAULT_COLOR);

        foreach($colors as $color) {
            $i++;
            $line[] = [
                'text' => $color->emoji,
                'callback_data' => CDM::toString([
                    CDM::EVENT => $event,
                    CDM::TEXT => $color->shortname
                ])
            ];
            if($i>=5) {
                $buttonsArray[] = $line;
                $line = [];
                $i = 0;
            }
        }

        if(!empty($line)) {
            $buttonsArray[] = $line;
        }

        return $buttonsArray;
    }

    public static function getAvailableColors(User|null $user = null, bool $ignoreExtraColors = false)
    {
        if($user && $user->isVipInArray(['dev', 'ultra_vip'])) {
            return self::all();
        }

        $query = self::where('is_free', true);
        
        if ($user) {
            $query->orWhereHas('creator', function ($query) use ($user) {
                $query->where('id', $user->id);
            });
        }

        if($user && $user->isVipType('vip')) {
            return $query->orWhereHas('event')->get();
        }

        if(!$ignoreExtraColors) {
            $query->orWhereHas('event', function ($query) {
                $query->whereRaw("DATE_FORMAT(start_at, '%m-%d') <= DATE_FORMAT(CURDATE(), '%m-%d')")
                        ->whereRaw("DATE_FORMAT(end_at, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')");
            });
        }
        
        return $query->get();
    }

    public static function isColorAllowedToUser(User $user, string $color, bool $ignoreExtraColors = false) {
        $availableColors = self::getAvailableColors($user, $ignoreExtraColors);

        return $availableColors->contains(function ($availableColor) use ($color) {
            return $availableColor->shortname === $color;
        });
    }

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
