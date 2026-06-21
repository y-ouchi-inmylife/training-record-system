<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // 「3級、2025年4月取得」のような取得時期を含む記述に対応するため100文字に拡張
            $table->string('disability_physical_grade', 100)->nullable()->change();
            $table->string('disability_mental_grade', 100)->nullable()->change();
            $table->string('disability_intellectual_grade', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('disability_physical_grade', 10)->nullable()->change();
            $table->string('disability_mental_grade', 10)->nullable()->change();
            $table->string('disability_intellectual_grade', 10)->nullable()->change();
        });
    }
};
