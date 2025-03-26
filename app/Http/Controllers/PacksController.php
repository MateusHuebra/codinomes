<?php

namespace App\Http\Controllers;

use App\Actions\Game\Guess;
use App\Models\Card;
use App\Models\Pack;
use App\Models\User;
use App\Services\AppString;
use Exception;
use Illuminate\Http\Request;
use TelegramBot\Api\BotApi;
use Validator;

class PacksController extends Controller
{

    public function get(Request $request) {
        $pack = Pack::find($request->input('id'));
        $cards = [];
        foreach($pack->cards()->get() as $card) {
            $cards[] = $card->text;
        }
        $pack->cards = implode(PHP_EOL, $cards);
        return $pack;
    }

    public function getAll(Request $request) {
        echo 'running   ';
        $output = shell_exec('curl https://loca.lt/mytunnelpassword');
        var_dump($output);die;

        if(!$request->input('id') || $request->input('id') == env('TG_MY_ID')) {
            return Pack::all();
        }
        return Pack::where('user_id', $request->input('id'))->get();
    }

    public function delete(int $id) {
        $pack = Pack::find($id);
        $pack->cards()->delete();
        $pack->chats()->detach();
        return $pack->delete();
    }

    public function approve(int $id) {
        $pack = Pack::find($id);
        $pack->status = 'public';
        $pack->save();

        $user = User::find($pack->user_id);
        $text = AppString::get('settings.pack_approved', [
            'name' => AppString::parseMarkdownV2($pack->name)
        ], $user->language);

        $bot = new BotApi(env('TG_TOKEN'));
        $bot->sendMessage($user->id, $text, 'MarkdownV2');
    }

    public function deny(Request $request) {
        $pack = Pack::find($request->input('id'));
        $pack->status = 'private';
        $pack->save();

        $user = User::find($pack->user_id);
        $text = AppString::get('settings.pack_denied', [
            'name' => AppString::parseMarkdownV2($pack->name),
            'text' => AppString::parseMarkdownV2($request->input('text'))
        ], $user->language);
        
        $bot = new BotApi(env('TG_TOKEN'));
        $bot->sendMessage($user->id, $text, 'MarkdownV2');
    }

    public function save(Request $request) {
        $data = $request->all();
        $data['cards'] = explode(PHP_EOL, $data['cards']);
        if($data['user_id'] == env('TG_MY_ID')) {
            $data['user_id'] = null;
        }
        $validator = Validator::make($data, [
            'name' => 'required|min:3|max:32',
            'status' => 'required',
            'language' => 'required|max:5',
            'cards' => 'required|array',
            'cards.*' => 'max:16|regex:'.Guess::REGEX
        ]);

        if($validator->fails()){
            return response($validator->errors(), 400);
        }

        $pack = Pack::findOrNew($request->input('id')??null);
        $pack->name = $data['name'];
        $pack->language = $data['language'];
        $pack->status = $data['status'];
        $pack->user_id = $data['user_id'];
        $pack->save();

        $cards = [];
        if($request->input('cards')) {
            foreach ($data['cards'] as $newCard) {
                $card = new Card;
                $card->text = trim($newCard);
                $card->pack_id = $pack->id;
                $cards[] = $card;
            }
        }
        $pack->cards()->delete();
        $pack->cards()->saveMany($cards);

        if($data['status'] != 'public') {
            $bot = new BotApi(env('TG_TOKEN'));
            try {
                $bot->sendMessage(env('TG_MY_ID'), "Saved pack!\n$pack->name\n$pack->id $pack->status");
            } catch(Exception $e) {}
        }

        return $pack->push();
    }
}
