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
        // A signed-in learner's progress, in the same shape the apps keep on the device.
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('review_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_key', 40);
            $table->unsignedBigInteger('due');
            $table->unsignedInteger('interval');
            $table->float('ease');
            $table->unsignedInteger('reps');
            $table->unsignedInteger('lapses');
            $table->timestamps();

            $table->unique(['user_id', 'item_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_cards');
        Schema::dropIfExists('lesson_progress');
    }
};
