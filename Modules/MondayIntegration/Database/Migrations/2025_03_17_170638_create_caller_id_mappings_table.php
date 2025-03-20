<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallerIdMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create caller_id_mappings table
        Schema::create('monday_caller_id_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_id');  // Phone number, email, or other identifier
            $table->string('alternate_id')->nullable();  // Secondary identifier (optional)
            $table->string('monday_item_id');  // Monday.com item ID
            $table->string('board_id')->nullable();  // Monday.com board ID for dual-board support
            $table->string('channel', 50);  // Communication channel (whatsapp, email, etc.)
            $table->unsignedInteger('conversation_id')->nullable();  // Related conversation ID
            $table->timestamp('last_used_at')->nullable();  // Timestamp of last usage
            $table->float('confidence_score')->default(1.0);  // Match confidence score (0.0-1.0)
            $table->timestamps();
        });
        
        // Create board_templates table
        Schema::create('monday_board_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');  // Template name
            $table->text('description')->nullable();  // Template description
            $table->json('columns');  // JSON structure of columns
            $table->json('default_mappings')->nullable();  // JSON structure of default field mappings
            $table->boolean('is_system')->default(false);  // Flag for system vs user templates
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
        Schema::dropIfExists('monday_caller_id_mappings');
        Schema::dropIfExists('monday_board_templates');
    }
}
