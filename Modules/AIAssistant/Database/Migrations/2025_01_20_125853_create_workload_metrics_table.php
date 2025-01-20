<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkloadMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workload_metrics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('active_tickets')->default(0);
            $table->integer('completed_today')->default(0);
            $table->integer('average_response_time')->nullable();
            $table->integer('sla_breaches')->default(0);
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
           
            $table->index('active_tickets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workload_metrics');
    }
}
