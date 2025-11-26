<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Script;
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('script_scenes', function (Blueprint $table) {
            $table->bigIncrements(column: 'id');
            $table->integer('order')->unsigned()->comment('Scene sequence, 1-based');
            $table->text('headline');
            $table->text('visual');
            $table->json('voiceover');
            $table->string('transition');
            $table->float('duration')->nullable();
            $table->string('sound_effect')->nullable();
            $table->foreignIdFor(Script::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_scenes');
    }
};
