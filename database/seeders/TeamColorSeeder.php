<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TeamColor;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $events = Event::all()->keyBy('shortname');
        $users = User::all()->keyBy('id');

        $colors = [
            [
                'shortname' => 'red',
                'emoji' => '🔴',
                'is_free' => true,
            ],
            [
                'shortname' => 'blue',
                'emoji' => '🔷',
                'is_free' => true,
            ],
            [
                'shortname' => 'pink',
                'emoji' => '🩷',
                'is_free' => true,
            ],
            [
                'shortname' => 'orange',
                'emoji' => '🔶',
                'is_free' => true,
            ],
            [
                'shortname' => 'purple',
                'emoji' => '🟣',
                'is_free' => true,
            ],
            [
                'shortname' => 'green',
                'emoji' => '♻️',
                'is_free' => true,
            ],
            [
                'shortname' => 'yellow',
                'emoji' => '⭐️',
                'is_free' => true,
            ],
            [
                'shortname' => 'gray',
                'emoji' => '🩶',
                'is_free' => true,
            ],
            [
                'shortname' => 'brown',
                'emoji' => '🍪',
                'is_free' => true,
            ],
            [
                'shortname' => 'cyan',
                'emoji' => '🧩',
                'is_free' => true,
            ],
            [
                'shortname' => 'easter',
                'emoji' => '🥚',
                'event_id' => $events->get('easter')?->id,
                'creator_id' => $users->get(668597631)?->id, // Leticia ADM
            ],
            [
                'shortname' => 'bunny',
                'emoji' => '🐰',
                'event_id' => $events->get('easter')?->id,
            ],
            [
                'shortname' => 'rbow',
                'emoji' => '🌈',
                'event_id' => $events->get('pride')?->id,
            ],
            [
                'shortname' => 'cotton',
                'emoji' => '🏳️‍⚧️',
                'event_id' => $events->get('pride')?->id,
            ],
            [
                'shortname' => 'flower',
                'emoji' => '💐',
                'event_id' => $events->get('pride')?->id,
            ],
            [
                'shortname' => 'dna',
                'emoji' => '🧬',
                'event_id' => $events->get('pride')?->id,
            ],
            [
                'shortname' => 'moon',
                'emoji' => '🌗',
                'event_id' => $events->get('pride')?->id,
            ],
            [
                'shortname' => 'pflag',
                'emoji' => '🇧🇷',
                'event_id' => $events->get('independence')?->id,
            ],
            [
                'shortname' => 'canary',
                'emoji' => '🐤',
                'event_id' => $events->get('independence')?->id,
            ],
            [
                'shortname' => 'south',
                'emoji' => '🌌',
                'event_id' => $events->get('independence')?->id,
            ],
            [
                'shortname' => 'jacko',
                'emoji' => '🎃',
                'event_id' => $events->get('halloween')?->id,
            ],
            [
                'shortname' => 'web',
                'emoji' => '🕸',
                'event_id' => $events->get('halloween')?->id,
            ],
            [
                'shortname' => 'bat',
                'emoji' => '🦇',
                'event_id' => $events->get('halloween')?->id,
            ],
            [
                'shortname' => 'snow',
                'emoji' =>  "❄️",
                'event_id' => $events->get('christmas')?->id,
            ],
            [
                "shortname"  => "tree",
                "emoji"  => "🎄",
                "event_id" => $events->get('christmas')?->id,
            ],
            [
                "shortname"  => "bonnet",
                "emoji"  => "🎅",
                "event_id" => $events->get('christmas')?->id,
            ],
            [
                "shortname"  => "wood",
                "emoji"  => "🪵",
            ],
            [
                "shortname"  => "metal",
                "emoji"  => "🔧",
            ],
            [
                "shortname"  => "leoprd",
                "emoji"  => "🐆",
                'creator_id' => $users->get(668597631)?->id, // Leticia ADM
            ],
        ];

        foreach ($colors as $color) {
            TeamColor::updateOrCreate(
                ['shortname' => $color['shortname']],
                $color
            );
        }
    }
}
