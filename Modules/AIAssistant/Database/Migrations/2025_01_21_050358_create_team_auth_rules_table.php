<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamAuthRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_auth_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->string('rule_type');
            $table->json('conditions');
            $table->json('allowed_actions');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['team_id', 'rule_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_auth_rules');
    }
}
