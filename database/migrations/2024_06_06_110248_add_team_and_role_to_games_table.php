<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamAndRoleToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('status', 8)->change();
            $table->enum('team', ['a', 'b'])->nullable()->after('status');
            $table->enum('role', ['master', 'agent'])->nullable()->after('team');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->enum('status', ['creating', 'master_a', 'master_b', 'agent_a', 'agent_b'])->change();
            $table->dropColumn('team');
            $table->dropColumn('role');
            $table->dropColumn('created_at');
        });
    }
}
