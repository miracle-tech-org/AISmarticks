<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutingRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('conditions')->nullable(); // JSON conditions
            $table->integer('priority')->default(0);
            $table->integer('team_id')->nullable();
            $table->json('required_skills')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routing_rules');
    }
}
