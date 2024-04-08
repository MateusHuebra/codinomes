<?php

namespace App\Http\Controllers;

use App\Models\TelegramUpdate;
use Illuminate\Http\Request;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use App\Services\ServerLog;
use App\UpdateHandlers\Commands;
use App\UpdateHandlers\CallbackQueries;
use TelegramBot\Api\Types\Update;

class CodinomesController extends Controller
{

    public function listen(Request $request) {
        ServerLog::log('start -----> CodinomesController > listen');
        $bot = new BotApi(env('TG_TOKEN'));
        $client = new Client(env('TG_TOKEN'));
        $updateRawData = $client->getRawBody();
        $update = Update::fromResponse(BotApi::jsonValidate($updateRawData, true));
        ServerLog::log('update raw data: '.$updateRawData);

        TelegramUpdate::dieIfAlreadyExistsOrSave($update->getUpdateId());

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
