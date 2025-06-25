<?php

use Database\Seeders\TeamColorSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamColorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_colors', function (Blueprint $table) {
            $table->id();
            $table->string('shortname', 6)->unique();
            $table->string('emoji', 16);

            $table->boolean('is_free')->default(false);

            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->onDelete('set null');

            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
        });

        (new TeamColorSeeder)->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_colors');
    }
}
