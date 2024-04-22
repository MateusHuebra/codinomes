<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use Illuminate\Http\Request;

class PacksController extends Controller
{

    public function get(Request $request) {
       return Pack::where('user_id', $request->input('id'))->get();
    }

}
