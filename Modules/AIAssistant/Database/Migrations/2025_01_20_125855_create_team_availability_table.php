<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_availability', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->json('schedule'); // Working hours, holidays, etc.
            $table->boolean('is_available')->default(true);
            $table->timestamp('next_available_at')->nullable();
            $table->text('unavailability_reason')->nullable();
            $table->timestamps();

            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_availability');
    }
}
