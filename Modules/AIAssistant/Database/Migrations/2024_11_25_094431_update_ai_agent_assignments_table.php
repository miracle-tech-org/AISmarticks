<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAiAgentAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_agent_assignments', function (Blueprint $table) {
            $table->integer('monthly_usage_limit')->after('response_mode');
            $table->integer('usage_alert_threshold')->after('monthly_usage_limit');
            $table->boolean('alert_triggered')->after('usage_alert_threshold')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_agent_assignments', function (Blueprint $table) {
            $table->dropColumn('monthly_usage_limit');
            $table->dropColumn('usage_alert_threshold');
            $table->dropColumn('alert_triggered');
        });
    }
}
