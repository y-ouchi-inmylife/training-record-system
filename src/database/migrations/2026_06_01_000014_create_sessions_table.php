<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');

            $table->index('user_id', 'sessions_user_id_index');
            $table->index('last_activity', 'sessions_last_activity_index');

            // user_id への外部キーは張らない（trainers/clients のマルチguardで単一テーブルFKが破綻するため。
            // トレーナー削除時のセッション掃除が必要になればアプリ側で担保する）。
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
