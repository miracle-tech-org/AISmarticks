<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('openai_api_key');
            $table->string('model')->default('gpt-4');
            $table->text('system_prompt')->nullable();
            $table->text('faqs')->nullable();
            $table->text('domain_specific_responses')->default('');
            $table->enum('response_mode', ['auto','suggest', 'both'])->default('suggest');
            $table->integer('monthly_limit')->default(10000);
            $table->integer('monthly_usage')->default(0);
            $table->integer('monthly_alert_threshold')->default(80);
            $table->integer('daily_limit')->default(500);
            $table->integer('daily_usage')->default(0);
            $table->integer('daily_alert_threshold')->default(80);
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
        Schema::dropIfExists('ai_agents');
    }
}
