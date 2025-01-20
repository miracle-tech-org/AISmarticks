<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgentSpecializationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_specializations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('skill');
            $table->integer('proficiency_level')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'skill']);
            $table->index('skill');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_specializations');
    }
}
