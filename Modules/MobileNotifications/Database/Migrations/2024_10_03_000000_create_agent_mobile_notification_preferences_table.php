<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgentMobileNotificationPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_mobile_notification_preferences', function (Blueprint $table) {
            $table->increments('id'); // Changed to bigIncrements for consistency if users.id is bigInteger
            $table->integer('user_id');
            $table->string('notification_type', 191); // Max string length for utf8mb4 unique keys
            $table->boolean('is_enabled')->default(true);
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
        Schema::dropIfExists('agent_mobile_notification_preferences');
    }
}
