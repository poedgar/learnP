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
        // What the apps show instead of the sandbox language, e.g. "Docker" for a course run in PHP.
        Schema::table('courses', function (Blueprint $table) {
            $table->string('label')->nullable()->after('language');
        });

        // Learning paths: an ordered list of courses. A course can be on several.
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('course_track', function (Blueprint $table) {
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');

            $table->primary(['track_id', 'course_id']);
        });

        // Anonymous counts only, never who: enough to find the lessons that need rewriting.
        Schema::create('challenge_stats', function (Blueprint $table) {
            $table->foreignId('challenge_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('checks')->default(0);
            $table->unsignedInteger('passes')->default(0);
            $table->unsignedInteger('first_try_passes')->default(0);
            $table->unsignedInteger('hint_views')->default(0);
            $table->unsignedInteger('solution_views')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_question_stats', function (Blueprint $table) {
            $table->foreignId('quiz_question_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('answers')->default(0);
            $table->unsignedInteger('correct')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_question_stats');
        Schema::dropIfExists('challenge_stats');
        Schema::dropIfExists('course_track');
        Schema::dropIfExists('tracks');
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
