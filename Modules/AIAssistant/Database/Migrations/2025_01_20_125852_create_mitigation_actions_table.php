<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMitigationActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mitigation_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->string('anomaly_type');
            $table->string('severity');
            $table->json('actions_taken')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('resolution')->nullable();
            $table->integer('effectiveness_score')->nullable();
            $table->boolean('automated_response')->default(false);
            $table->string('response_type')->nullable();
            $table->text('review_notes')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mitigation_actions');
    }
}
