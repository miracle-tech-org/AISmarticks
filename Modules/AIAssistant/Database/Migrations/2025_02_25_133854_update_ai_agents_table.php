<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAiAgentsTable extends Migration
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
            $table->text('timeoutMessage')->default('');
            $table->string('timeoutTag')->default('');
            $table->text('failoverMessage')->default('');
            $table->string('failoverTag')->default('');

            $table->boolean('customerDetailJson')->default(0);
            $table->boolean('conversationHistoryJson')->default(0);
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
