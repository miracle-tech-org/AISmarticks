<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('working_hours')->nullable();
            $table->json('notification_settings')->nullable();
            $table->integer('max_workload')->default(50);
            $table->timestamps();
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teams');
    }
}
