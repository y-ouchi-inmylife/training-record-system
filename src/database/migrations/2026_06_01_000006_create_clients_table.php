<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('internal_id', 10);
            $table->date('initial_consultation_date');

            // カテゴリー1: 基本情報
            $table->string('last_name', 50)->nullable()->comment('姓');
            $table->string('first_name', 50)->nullable()->comment('名');
            $table->string('last_name_kana', 50)->nullable()->comment('姓かな');
            $table->string('first_name_kana', 50)->nullable()->comment('名かな');
            $table->date('birth_date')->nullable()->comment('生年月日');
            $table->string('gender', 10)->nullable()->comment('性別');

            // カテゴリー2: 連絡先
            $table->string('phone1', 20)->nullable()->comment('電話番号1（携帯）');
            $table->string('phone2', 20)->nullable()->comment('電話番号2（自宅）');
            $table->string('phone3', 20)->nullable()->comment('電話番号3（緊急連絡先）');
            $table->string('email', 255)->nullable()->comment('メールアドレス');
            $table->string('postal_code', 10)->nullable()->comment('郵便番号');
            $table->string('address1', 50)->nullable()->comment('住所1（都道府県）');
            $table->string('address2', 50)->nullable()->comment('住所2（市区町村）');
            $table->string('address3', 100)->nullable()->comment('住所3（町名・番地）');
            $table->string('address4', 100)->nullable()->comment('住所4（建物名・部屋番号）');

            // クライアント閲覧機能（柱2）用の認証カラム
            $table->string('password', 255)->nullable()->comment('クライアント閲覧機能のパスワードのハッシュ値（bcrypt）。閲覧解放後、本人が設定するまでNULL');
            $table->boolean('is_viewable')->default(false)->comment('クライアント閲覧解放フラグ');

            // カテゴリー7: 支援管理
            $table->unsignedBigInteger('primary_trainer_id')->nullable();
            $table->unsignedBigInteger('support_status_id')->nullable();

            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス（SHOW CREATE TABLE の出現順）
            $table->unique('internal_id', 'clients_internal_id_unique');
            $table->index('primary_trainer_id', 'clients_primary_trainer_idx');
            $table->index('initial_consultation_date', 'clients_initial_date_idx');
            $table->index('created_at', 'clients_created_at_idx');
            $table->index('support_status_id', 'clients_support_status_idx');
            $table->index('updated_by', 'clients_updated_by_foreign');
            $table->unique('email', 'clients_email_unique');

            // 外部キー（作成順）
            $table->foreign('primary_trainer_id', 'clients_primary_trainer_id_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('support_status_id', 'clients_support_status_id_foreign')
                ->references('id')->on('support_statuses')->nullOnDelete();
            $table->foreign('updated_by', 'clients_updated_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });

        // CHECK 制約（全角文字を含む文字列値は既存マイグレーションから正確にコピー）
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_gender_check CHECK (gender IS NULL OR gender IN ('男', '女', 'その他'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
