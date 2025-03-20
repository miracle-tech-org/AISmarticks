<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMondaySyncLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monday_sync_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('action_type')->index(); // create, update, thread, status, webhook, manual_sync
            $table->string('source_type')->index(); // api, webhook, user, system
            $table->string('monday_board_id')->nullable()->index();
            $table->string('monday_item_id')->nullable()->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->string('status')->index(); // pending, processing, completed, failed, retrying
            $table->text('error_message')->nullable();
            $table->text('request_data')->nullable(); // JSON encoded request data
            $table->text('response_data')->nullable(); // JSON encoded response data
            $table->unsignedInteger('retry_count')->default(0);
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
        Schema::dropIfExists('monday_sync_logs');
    }
}
