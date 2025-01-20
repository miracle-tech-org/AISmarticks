<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlaConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sla_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('response_time_minutes');
            $table->integer('resolution_time_minutes');
            $table->json('priority_adjustments')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sla_configurations');
    }
}
