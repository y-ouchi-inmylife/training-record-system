<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_files', function (Blueprint $table) {
            $table->string('title', 255)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('audio_files', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->change();
        });
    }
};
