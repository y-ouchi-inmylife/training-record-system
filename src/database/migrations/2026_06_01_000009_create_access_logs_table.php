<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->string('action', 100)->comment('login, view_client, edit_client 等');
            $table->string('target_type', 50)->nullable()->comment('対象モデル名');
            $table->unsignedBigInteger('target_id')->nullable()->comment('対象レコードID');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'created_at'], 'access_logs_trainer_id_created_at_idx');
            $table->index(['action', 'created_at'], 'access_logs_action_created_at_idx');

            $table->foreign('trainer_id', 'access_logs_trainer_id_foreign')
                ->references('id')->on('trainers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
