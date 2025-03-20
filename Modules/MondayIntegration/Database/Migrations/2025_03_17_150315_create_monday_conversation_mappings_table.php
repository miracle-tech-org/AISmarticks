<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMondayConversationMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monday_conversation_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id')->unsigned();
            $table->string('monday_item_id');
            $table->string('board_id');
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
        Schema::dropIfExists('monday_conversation_mappings');
    }
}
