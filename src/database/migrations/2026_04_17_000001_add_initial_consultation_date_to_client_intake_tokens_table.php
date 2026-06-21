<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_intake_tokens', function (Blueprint $table) {
            $table->date('initial_consultation_date')->nullable()->after('memo');
        });
    }

    public function down(): void
    {
        Schema::table('client_intake_tokens', function (Blueprint $table) {
            $table->dropColumn('initial_consultation_date');
        });
    }
};
