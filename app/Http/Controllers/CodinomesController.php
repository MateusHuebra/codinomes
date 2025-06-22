<?php

namespace App\Http\Controllers;

use App\Services\AppString;
use App\UpdateHandlers\Factory as HandlerFactory;
use App\Adapters\UpdateTypes\Factory as UpdateFactory;
use Illuminate\Http\Request;
use App\Models\TelegramUpdate;
use App\Services\Telegram\BotApi;
use App\Services\ServerLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;
use Throwable;

class CodinomesController extends Controller
{

    public function listen(Request $request) {
        define('CONTROLLER_START', microtime(true));
        ServerLog::log('start -----> CodinomesController > listen');
        $bot = new BotApi(env('TG_TOKEN'));
        $updateRawData = file_get_contents('php://input');
        $update = Update::fromResponse(BotApi::jsonValidate($updateRawData, true));
        $update = UpdateFactory::build($update);
        ServerLog::log('update raw data: '.$updateRawData);

        if(!$update) {
            die;
        }
        TelegramUpdate::dieIfAlreadyExistsOrSave($update->getUpdateId());

        $updateHandler = HandlerFactory::build($update);
        AppString::setLanguage($update);
        $action = $updateHandler->getAction($update);

        try {
            if($action) {
                DB::beginTransaction();
                $action->run($update, $bot);
                DB::commit();
            }
        } catch(Throwable $e) {
            DB::rollBack();
            $errorMessage = '\#Exception at '.ServerLog::$updateId.':```java'.PHP_EOL;
            $errorMessage.= $e->getMessage().PHP_EOL.PHP_EOL;
            $errorMessage.= $e->getFile().' line '.$e->getLine().'```';
            ServerLog::log($errorMessage);
            $bot->sendMessage(env('TG_LOG_ID'), $errorMessage, 'MarkdownV2');

            if(!preg_match("/(timed out|timeout|are exactly the same)/u", $e->getMessage())) {
                $bot->sendMessage($update->getFromId(), AppString::get('error.report', [
                    'code' => $update->getUpdateId()
                ]));
            }
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stack' => $e->getTraceAsString()
            ]);
        }
        ServerLog::log('end -----> CodinomesController > listen');
    }

}
