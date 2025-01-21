<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamSlaConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_sla_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('response_time_minutes');
            $table->integer('resolution_time_minutes');
            $table->json('priority_multipliers')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_sla_configs');
    }
}
