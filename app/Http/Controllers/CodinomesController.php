<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use App\Services\ServerLog;
use App\UpdateHandlers\Commands;
use App\UpdateHandlers\CallbackQueries;

class CodinomesController extends Controller
{

    public function listen(Request $request) {
        ServerLog::log('start -----> CodinomesController > listen');
        $bot = new BotApi(env('TG_TOKEN'));
        $client = new Client(env('TG_TOKEN'));
        ServerLog::log('update raw data: '.$client->getRawBody());

        $client = $this->addEventsByUpdateType($client, $bot, [
            Commands::class,
            CallbackQueries::class
        ]);

        $client->run();
        ServerLog::log('end -----> CodinomesController > listen');
    }

    private function addEventsByUpdateType(Client $client, BotApi $bot, Array /* of UpdateHandler */ $handlers) : Client {
        foreach($handlers as $handler) {
            $client = $handler::addEvents($client, $bot);
        }
        return $client;
    }

}
