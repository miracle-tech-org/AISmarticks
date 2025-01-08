<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAimiddlewareBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // AI Agents table
        Schema::create('ai_middleware_ai_agents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('model');
            $table->text('system_prompt')->nullable();
            $table->json('default_tags')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        // AI Interaction Logs table
        Schema::create('ai_middleware_interaction_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('agent_id');
            $table->text('user_query');
            $table->text('ai_response');
            $table->json('tags')->nullable();
            $table->integer('tokens_used');
            $table->string('sentiment')->nullable();
            $table->decimal('confidence', 4, 2)->nullable();
            $table->string('priority')->nullable();
            $table->integer('rating')->nullable();
            $table->decimal('response_time', 8, 2)->nullable(); // in milliseconds
            $table->timestamps();

            // $table->foreign('ticket_id')
            //     ->references('id')
            //     ->on('tickets')
            //     ->onDelete('cascade');

            // $table->foreign('agent_id')
            //     ->references('id')
            //     ->on('ai_middleware_ai_agents')
            //     ->onDelete('cascade');

            $table->index(['created_at']);
            $table->index(['rating']);
            $table->index(['tokens_used']);
        });

        // AI Training Data table
        Schema::create('ai_middleware_training_data', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('interaction_id');
            $table->text('query');
            $table->text('response');
            $table->text('correct_response')->nullable();
            $table->text('improved_response')->nullable();
            $table->text('feedback_text')->nullable();
            $table->integer('rating')->nullable();
            $table->string('category')->nullable();
            $table->string('improvement_reason')->nullable();
            $table->json('training_metadata')->nullable();
            $table->timestamps();

            // $table->foreign('interaction_id')
            //     ->references('id')
            //     ->on('ai_middleware_interaction_logs')
            //     ->onDelete('cascade');

            $table->index(['category']);
            $table->index(['rating']);
            $table->index(['created_at']);
        });

        // Insert default module options
        $defaults = [
            'ai_middleware.timeout' => 30,
            'ai_middleware.max_tokens' => 2000,
            'ai_middleware.model' => 'gpt-4',
            'ai_middleware.monthly_usage_limit' => 10000,
            'ai_middleware.alert_threshold' => 80,
        ];

        foreach ($defaults as $key => $value) {
            if (class_exists(\App\Option::class)) {
                \App\Option::set($key, $value);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_middleware_training_data');
        Schema::dropIfExists('ai_middleware_interaction_logs');
        Schema::dropIfExists('ai_middleware_ai_agents');
    }
}
