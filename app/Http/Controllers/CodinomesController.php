<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramUpdate;
use App\Services\Telegram\BotApi;
use App\Services\ServerLog;
use App\UpdateHandlers\Commands;
use App\UpdateHandlers\CallbackQueries;
use App\UpdateHandlers\Messages;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;
use Throwable;

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
            CallbackQueries::class,
            Messages::class
        ]);

        try {
            $client->run();
        } catch(Throwable $e) {
            $errorMessage = '\#Exception at '.ServerLog::$updateId.':```java'.PHP_EOL;
            $errorMessage.= $e->getMessage().PHP_EOL.PHP_EOL;
            $errorMessage.= $e->getFile().' line '.$e->getLine().'```';
            $bot->sendMessage(env('TG_MYID'), $errorMessage, 'MarkdownV2');
        }
        ServerLog::log('end -----> CodinomesController > listen');
    }

    private function addEventsByUpdateType(Client $client, BotApi $bot, Array /* of UpdateHandler */ $handlers) : Client {
        foreach($handlers as $handler) {
            $client = $handler::addEvents($client, $bot);
        }
        return $client;
    }

}
