<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $events = [
            [
                'shortname' => 'easter',
                'start_at' => '0004-03-22',
                'end_at' => '0004-04-25',
            ],
            [
                'shortname' => 'pride',
                'start_at' => '0004-06-01',
                'end_at' => '0004-06-30',
            ],
            [
                'shortname' => 'independence',
                'start_at' => '0004-09-01',
                'end_at' => '0004-09-30',
            ],
            [
                'shortname' => 'halloween',
                'start_at' => '0004-10-01',
                'end_at' => '0004-11-01',
            ],
            [
                'shortname' => 'christmas',
                'start_at' => '0004-12-01',
                'end_at' => '0004-12-31',
            ],
        ];
        
        foreach ($events as $event) {
            Event::firstOrCreate(
                ['shortname' => $event['shortname']],
                $event
            );
        }
    }
}
