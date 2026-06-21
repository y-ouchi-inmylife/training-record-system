<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('initial_consultation_date')->nullable(false)->change();
            $table->string('family_relationship', 20)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('initial_consultation_date')->nullable()->change();
            $table->string('family_relationship', 20)->nullable()->change();
        });
    }
};
