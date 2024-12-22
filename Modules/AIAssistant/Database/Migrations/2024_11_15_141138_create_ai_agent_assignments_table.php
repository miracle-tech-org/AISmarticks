<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiAgentAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_agent_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ai_agent_id');
            $table->unsignedInteger('mailbox_id')->nullable();
            $table->unsignedInteger('conversation_id')->nullable();
            $table->enum('response_mode', ['auto','suggest', 'both'])->default('suggest');
            $table->integer('monthly_usage_limit')->default(10000);
            $table->integer('current_usage')->default(0);
            $table->integer('usage_alert_threshold')->default(80);
            $table->integer('daily_limit')->default(500);
            $table->integer('daily_usage')->default(0);
            $table->integer('daily_usage_alert')->default(80);
            $table->boolean('alert_triggered')->default(false);
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('ai_agent_assignments');
    }
}
