<?php

namespace App\Http\Controllers;

use App\Services\AppString;
use App\UpdateHandlers\Factory;
use Illuminate\Http\Request;
use App\Models\TelegramUpdate;
use App\Services\Telegram\BotApi;
use App\Services\ServerLog;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;
use Throwable;

class CodinomesController extends Controller
{

    public function listen(Request $request) {
        define('CONTROLLER_START', microtime(true));
        ServerLog::log('start -----> CodinomesController > listen');
        $bot = new BotApi(env('TG_TOKEN'));
        $updateRawData = file_get_contents('php://input');
        $update = Update::fromResponse(BotApi::jsonValidate($updateRawData, true));
        ServerLog::log('update raw data: '.$updateRawData);

        TelegramUpdate::dieIfAlreadyExistsOrSave($update->getUpdateId());

        $updateHandler = Factory::build($update);
        $update = Factory::getSpecificUpdateType();
        AppString::setLanguage($update);
        $action = $updateHandler->getAction($update);

        try {
            if($action) {
                $action->run($update, $bot);
            }
        } catch(Throwable $e) {
            $errorMessage = '\#Exception at '.ServerLog::$updateId.':```java'.PHP_EOL;
            $errorMessage.= $e->getMessage().PHP_EOL.PHP_EOL;
            $errorMessage.= $e->getFile().' line '.$e->getLine().'```';
            $bot->sendMessage(env('TG_MY_ID'), $errorMessage, 'MarkdownV2');
        }
        ServerLog::log('end -----> CodinomesController > listen');
    }

}
