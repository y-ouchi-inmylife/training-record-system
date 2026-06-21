<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カウンセラーテーブル作成
     */
    public function up(): void
    {
        Schema::create('counselors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('カウンセラーの氏名');
            $table->string('login_id', 50)->unique()->comment('ログインID');
            $table->string('password', 255)->comment('パスワードのハッシュ値');
            $table->string('role', 20)->default('general')->comment('権限: admin / general');
            $table->boolean('is_locked')->default(false)->comment('アカウントロック状態');
            $table->timestamps();
        });

        // CHECK制約を追加
        DB::statement("ALTER TABLE counselors ADD CONSTRAINT counselors_role_check CHECK (role IN ('admin', 'general'))");
        DB::statement("ALTER TABLE counselors ADD CONSTRAINT counselors_login_id_check CHECK (login_id REGEXP '^[a-zA-Z0-9_]+$')");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('counselors');
    }
};
