<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->decimal('heures_reelles', 5, 2)->default(0);
            $table->boolean('over_44h')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropColumn(['heures_reelles', 'over_44h']);
        });
    }
};
