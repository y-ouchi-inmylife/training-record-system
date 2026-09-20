<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->comment('会員のID（外部キー）');
            $table->string('name', 50)->comment('名前。犬の名前はひらがな・カタカナが多く、よみ（かな）は持たない');
            $table->string('breed', 100)->nullable()->comment('犬種。自由入力（マスタは持たない）');
            $table->string('sex', 20)->nullable()->comment('性別（5-20 参照）');
            $table->date('birth_date')->nullable()->comment('誕生日。不明な場合があるため NULL 許容');
            $table->text('note')->nullable()->comment('備考');
            // 会員がダッシュボード（S-1402）から登録・差し替え・削除する写真の保存キー
            // （6-15-15、D-0700 注記「トレーニー写真の扱い」参照）。キー形式は
            // trainees/YYYYMM/{uuid}.jpg 固定（サーバ側で必ず JPEG に変換して保存）
            $table->string('photo_path', 500)->nullable()->comment('トレーニー写真の保存パス（キー）。写真未登録は NULL。リサイズ済み JPEG 1 枚のみ');
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス
            $table->index('client_id', 'trainees_client_id_idx');
            $table->index('updated_by', 'trainees_updated_by_foreign');

            // 外部キー
            $table->foreign('client_id', 'trainees_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('updated_by', 'trainees_updated_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });

        // CHECK 制約（Laravel schema builder では表現できないため DB::statement で追加）
        DB::statement("ALTER TABLE trainees ADD CONSTRAINT trainees_sex_check CHECK (sex IN ('male', 'female', 'unknown'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('trainees');
    }
};
