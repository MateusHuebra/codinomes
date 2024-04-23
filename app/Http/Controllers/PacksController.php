<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use Illuminate\Http\Request;

class PacksController extends Controller
{

    public function get(Request $request) {
        $pack = Pack::find($request->input('id'));
        $pack->cards;
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
        return $pack->delete();
    }
}
