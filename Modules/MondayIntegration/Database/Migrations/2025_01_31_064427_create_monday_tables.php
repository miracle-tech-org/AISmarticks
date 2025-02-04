<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMondayTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monday_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->text('value');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('monday_field_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('smarticks_field');
            $table->string('monday_column');
            $table->string('transform_rule')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('monday_sync_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('action_type', 50);
            $table->string('source_type', 50);
            $table->string('source_id');
            $table->string('monday_item_id')->nullable();
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
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
        Schema::dropIfExists('monday_settings');
        Schema::dropIfExists('monday_field_mappings');
        Schema::dropIfExists('monday_sync_logs');
    }
}
