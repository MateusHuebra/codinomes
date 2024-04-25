<?php

namespace App\Http\Controllers;

use App\Actions\Game\Guess;
use App\Models\Card;
use App\Models\Pack;
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
        if($request->input('id')) {
            return Pack::where('user_id', $request->input('id'))->get();
        }
        return Pack::all();
    }

    public function delete(int $id) {
        $pack = Pack::find($id);
        $pack->cards()->delete();
        $pack->chats()->detach();
        return $pack->delete();
    }

    public function save(Request $request) {
        $data = $request->all();
        $data['cards'] = explode(PHP_EOL, $data['cards']);
        $validator = Validator::make($data, [
            'name' => 'required|min:3|max:32',
            'status' => 'required',
            'language' => 'required|max:5',
            'cards' => 'required|array',
            'cards.*' => 'max:12|regex:'.Guess::REGEX
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
                $card->text = $newCard;
                $card->pack_id = $pack->id;
                $cards[] = $card;
            }
        }
        $pack->cards()->delete();
        $pack->cards()->saveMany($cards);

        $bot = new BotApi(env('TG_TOKEN'));
        try {
            $bot->sendMessage(env('TG_MY_ID'), 'New pack!');
        } catch(Exception $e) {}

        return $pack->push();
    }
}
