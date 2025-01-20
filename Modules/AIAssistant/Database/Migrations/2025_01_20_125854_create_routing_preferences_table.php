<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutingPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routing_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
            $table->string('distribution_method')->default('round_robin'); // round_robin, skill_based, load_balanced
            $table->json('custom_rules')->nullable();
            $table->boolean('auto_escalation_enabled')->default(false);
            $table->integer('escalation_threshold_minutes')->nullable();
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
        Schema::dropIfExists('routing_preferences');
    }
}
