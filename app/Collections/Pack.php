<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class Pack extends Collection {

    public function getCards() {
        foreach($this as $pack) {
            foreach ($pack->cards as $card) {
                $models[] = $card;
            }
        }
        return new Collection($models);
    }
    
}