<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id');
            $table->integer('from_user_id');
            $table->integer('to_user_id');
            $table->integer('from_team_id')->nullable();
            $table->integer('to_team_id')->nullable();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->index('transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_history');
    }
}
