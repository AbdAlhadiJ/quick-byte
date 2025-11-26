<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('openai_batches', function (Blueprint $table) {
            $table->id();
            $table->string('openai_batch_id')->unique();
            $table->string('action');
            $table->string('endpoint');
            $table->string('input_file_id');
            $table->string('output_file_id')->nullable();
            $table->string('error_file_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('error_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_batches');
    }
};
