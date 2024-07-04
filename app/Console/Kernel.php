<?php

namespace App\Console;

use App\Console\Scheduled\CheckTurnLeftTime;
use App\Console\Scheduled\DailyStatus;
use App\Console\Scheduled\DeleteOldLinesFromUpdatesTable;
use App\Console\Scheduled\DidYouKnow;
use App\Console\Scheduled\NotifyNewGames;
use App\Console\Scheduled\StopAbandonedLobbies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(new CheckTurnLeftTime)->everyMinute();
        $schedule->call(New NotifyNewGames)->everyMinute();
        $schedule->call(new StopAbandonedLobbies)->everyFiveMinutes();
        $schedule->call(new DidYouKnow)->dailyAt(18);
        $schedule->call(new DeleteOldLinesFromUpdatesTable)->daily();
        $schedule->call(new DailyStatus)->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
