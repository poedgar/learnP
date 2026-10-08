<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A follow-up query for SQL challenges that change data: it runs after the
     * learner's SQL and after the solution, and its results are compared.
     */
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->text('check_sql')->nullable()->after('solution');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('check_sql');
        });
    }
};
