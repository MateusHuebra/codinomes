<?php

namespace App\Models;

use App\Services\ServerLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUpdate extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = ['id'];

    static function dieIfAlreadyExistsOrSave(int $id) {
        ServerLog::$updateId = $id;
        if($id===0) { // 0 = simulated updates
            return;
        }
        if(self::find($id)) {
            ServerLog::log('id already used, ending process');
            die;
        }
        self::create([
            'id' => $id
        ]);
    }

}
