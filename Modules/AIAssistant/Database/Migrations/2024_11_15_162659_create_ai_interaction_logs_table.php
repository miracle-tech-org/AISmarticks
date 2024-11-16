<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiInteractionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_interaction_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ai_agent_id');
            $table->unsignedInteger('mailbox_id')->nullable();
            $table->unsignedInteger('conversation_id')->nullable();
            $table->text('input_text');
            $table->text('output_text');
            $table->integer('tokens_used');
            $table->timestamps();

            // $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->onDelete('cascade');
            // $table->foreign('mailbox_id')->references('id')->on('mailboxes')->onDelete('cascade');
            // $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_interaction_logs');
    }
}
