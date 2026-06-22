<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('設定キー');
            $table->text('value')->nullable()->comment('設定値');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE system_settings ADD CONSTRAINT system_settings_key_check CHECK (`key` REGEXP '^[a-z][a-z0-9_]*$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
