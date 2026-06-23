<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('カウンセラーの氏名');
            $table->string('login_id', 50)->unique()->comment('ログインID');
            $table->string('password', 255)->comment('パスワードのハッシュ値');
            // 旧 DEFAULT 'general' は確定済み修正方針により廃止（必須カラム化）
            $table->string('role', 20)->comment('権限: admin / general');
            $table->boolean('is_locked')->default(false)->comment('アカウントロック状態');
            $table->boolean('is_active')->default(true)->comment('アカウント有効フラグ');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->boolean('must_change_password')->default(false)->comment('初回ログイン時パスワード変更必須フラグ');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('display_order', 'trainers_display_order_index');
        });

        DB::statement("ALTER TABLE trainers ADD CONSTRAINT trainers_login_id_check CHECK (login_id REGEXP '^[a-zA-Z0-9_]+$')");
        DB::statement("ALTER TABLE trainers ADD CONSTRAINT trainers_role_check CHECK (role IN ('system_admin', 'admin', 'staff'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
