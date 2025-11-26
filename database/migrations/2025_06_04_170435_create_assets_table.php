<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ScriptScene;
use App\Enums\AssetStatus;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('status')->default(AssetStatus::PENDING);
            $table->string('remote_path')->nullable();
            $table->string('local_path')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('source');
            $table->string('type')->default('video');
            $table->foreignIdFor(\App\Models\Script::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(ScriptScene::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
