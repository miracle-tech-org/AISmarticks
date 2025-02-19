<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettingsToAiAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->integer('numberOfRetries')->default(0);
            $table->integer('delayOfRetries')->default(0);
            $table->string('timeoutMessage')->default('');
            $table->string('failoverMessage')->default('');
            $table->string('failoverAction')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_agents', function (Blueprint $table) {

        });
    }
}
