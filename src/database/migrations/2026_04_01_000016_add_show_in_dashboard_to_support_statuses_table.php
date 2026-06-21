<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_statuses', function (Blueprint $table) {
            $table->boolean('show_in_dashboard')->default(true)->after('sort_order')
                  ->comment('ダッシュボードに表示するか');
        });
    }

    public function down(): void
    {
        Schema::table('support_statuses', function (Blueprint $table) {
            $table->dropColumn('show_in_dashboard');
        });
    }
};
