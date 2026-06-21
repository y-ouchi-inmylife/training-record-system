<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カウンセラーから email カラムを削除する。
     *
     * 現在の導入先では「カウンセラーがメールアドレスを持って外部と
     * 連絡する運用」がないため、未使用となっている email カラムを
     * 完全削除する。将来必要になった時は、その時の要件に合わせて
     * 再設計する。
     */
    public function up(): void
    {
        // email 列が存在する場合のみ削除（fresh 環境では作成されないためスキップ）
        if (Schema::hasColumn('counselors', 'email')) {
            Schema::table('counselors', function (Blueprint $table) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            });
        }
    }

    public function down(): void
    {
        // email 列が無い場合のみ再作成（多重実行を防ぐ）
        if (!Schema::hasColumn('counselors', 'email')) {
            Schema::table('counselors', function (Blueprint $table) {
                $table->string('email', 255)->nullable()->unique()->comment('メールアドレス（任意）');
            });
        }
    }
};
