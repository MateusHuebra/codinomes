<?php

namespace App\Events;

use App\Services\Telegram\BotApi;

interface Event {

    static function getEvent(BotApi $bot) : callable;

  }