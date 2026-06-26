<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->comment('メディアの持ち主クライアント。登録時はアプリ側で必須。持ち主クライアント削除時にNULL（メディアはライブラリに残る）');
            $table->unsignedBigInteger('trainer_id')->nullable()->comment('アップロードしたトレーナー（登録者）。登録者削除時にNULL（メディアはライブラリに残る）');
            $table->string('type', 20)->comment("メディア種別: 'photo', 'video'");
            $table->string('title', 255)->nullable()->comment('表示名。未入力時は表示の際に original_filename をフォールバック表示');
            $table->string('original_filename', 255)->comment('アップロード時の元ファイル名');
            $table->string('original_path', 500)->comment('アップロードされた原本ファイルのオブジェクトストレージ上の保存パス（キー）');
            $table->string('display_path', 500)->nullable()->comment('ブラウザ表示用ファイルのオブジェクトストレージ上の保存パス（キー）。変換不要時は original_path と同値、変換待ち/中/失敗時はNULL');
            $table->string('thumbnail_path', 500)->nullable()->comment('サムネイルの保存パス（キー）。サムネイル生成は後フェーズのため当面NULL');
            $table->string('mime_type', 100)->comment('MIMEタイプ（image/jpeg, image/png, image/heic, video/mp4, video/quicktime 等）');
            $table->bigInteger('file_size')->nullable()->comment('ファイルサイズ（バイト）');
            $table->string('conversion_status', 20)->default('not_required')->comment("表示用変換の状態: 'not_required'（変換不要・jpeg/png/mp4）, 'pending'（変換待ち・heic/mov）, 'processing', 'done', 'error'");
            $table->timestamps();

            // インデックス
            $table->index('client_id', 'media_records_client_id_foreign');
            $table->index('trainer_id', 'media_records_trainer_id_foreign');
            $table->index('type', 'media_records_type_idx');
            $table->index('created_at', 'media_records_created_at_idx');

            // 外部キー（client_id → trainer_id の順）
            $table->foreign('client_id', 'media_records_client_id_foreign')
                ->references('id')->on('clients')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('trainer_id', 'media_records_trainer_id_foreign')
                ->references('id')->on('trainers')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        DB::statement("ALTER TABLE media_records ADD CONSTRAINT media_records_type_check CHECK (type IN ('photo', 'video'))");
        DB::statement("ALTER TABLE media_records ADD CONSTRAINT media_records_file_size_check CHECK (file_size IS NULL OR file_size >= 0)");
        DB::statement("ALTER TABLE media_records ADD CONSTRAINT media_records_conversion_status_check CHECK (conversion_status IN ('not_required', 'pending', 'processing', 'done', 'error'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('media_records');
    }
};
