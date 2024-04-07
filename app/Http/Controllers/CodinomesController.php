<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Telegram\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;
use App\Services\ServerLog;
use Exception;

class CodinomesController extends Controller
{
    
    public function listen(Request $request) {
        ServerLog::log('start -----> CodinomesController > listen');
        $bot = new BotApi(env('TG_TOKEN'));
        $update = $this->getUpdate();
        if($update->getMessage() && $update->getMessage()->getText()==='/ping') {
            $bot->sendMessage($update->getMessage()->getChat()->getId(), 'pong!');
        }
    }

    private function getUpdate() : Update {
        $client = new Client(env('TG_TOKEN'));
        $updateData = $client->getRawBody();
        ServerLog::log('update json: '.$updateData);
        return Update::fromResponse(BotApi::jsonValidate($updateData, true));
    }

}
