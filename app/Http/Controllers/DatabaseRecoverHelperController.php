<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseRecoverHelperController extends Controller
{

    public function run(Request $request) {
        die;
        DB::beginTransaction();
        echo '<pre>';
        $queriesString = $this->getQueriesString();

        $queries = collect(explode(PHP_EOL, $queriesString));

        $success = [];
        $fail = [];

        $queries->map(function($query) use (&$success, &$fail) {
            try {
                DB::statement($query);
                $success[] = $query;
            } catch(Exception $e) {
                $fail[] = $query;
            }
        });

        $this->listResult(collect($fail), 'falha');

        echo PHP_EOL.PHP_EOL.PHP_EOL.'----------------------------------------------'.PHP_EOL.PHP_EOL.PHP_EOL;

        $this->listResult(collect($success), 'sucesso');
        DB::commit();
    }

    private function listResult(Collection $resultsCollection, $name) 
    {
        echo $name . ' -> ' . count($resultsCollection) . ':';

        $resultsCollection->map(function($result) {
            echo PHP_EOL . $result;
        });
    }

    private function getQueriesString(): string
    {
        $path = resource_path('query');

        if (!file_exists($path)) {
            abort(404, 'Arquivo não encontrado');
        }
    
        return file_get_contents($path);
    }

}
