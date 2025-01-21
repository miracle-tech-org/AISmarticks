<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamWorkloadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_workload', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('active_tickets')->default(0);
            $table->integer('pending_tickets')->default(0);
            $table->float('average_response_time')->nullable();
            $table->float('average_resolution_time')->nullable();
            $table->timestamp('last_updated');
            $table->timestamps();
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_workload');
    }
}
