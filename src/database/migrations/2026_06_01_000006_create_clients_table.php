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
            $table->string('last_name', 50)->nullable()->comment('姓（本人）');
            $table->string('first_name', 50)->nullable()->comment('名（本人）');
            $table->string('last_name_kana', 50)->nullable()->comment('姓かな（本人）');
            $table->string('first_name_kana', 50)->nullable()->comment('名かな（本人）');
            $table->string('family_last_name', 50)->nullable()->comment('姓（家族）');
            $table->string('family_first_name', 50)->nullable()->comment('名（家族）');
            $table->string('family_last_name_kana', 50)->nullable()->comment('姓かな（家族）');
            $table->string('family_first_name_kana', 50)->nullable()->comment('名かな（家族）');
            $table->string('family_relationship', 20);
            $table->string('family_relationship_detail', 100)->nullable()->comment('関係の詳細');
            $table->date('birth_date')->nullable()->comment('生年月日（本人）');
            $table->integer('initial_age')->nullable()->comment('初回相談時の年齢');
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
            $table->string('nearest_station', 50)->nullable()->comment('最寄り駅');

            // カテゴリー3: 学歴
            $table->string('education_level', 20)->nullable()->comment('学歴');
            $table->text('education_detail')->nullable()->comment('学歴の詳細・備考');
            $table->string('education_status', 10)->nullable()->comment('学歴の状態');
            $table->boolean('education_dropout_expected')->nullable()->comment('中退見込フラグ');

            // カテゴリー4: 職歴
            $table->string('employment_type', 30)->nullable()->comment('雇用形態');
            $table->string('employment_hours', 20)->nullable()->comment('週の労働時間');
            $table->string('employment_period', 30)->nullable()->comment('雇用期間');
            $table->string('unemployment_period', 20)->nullable()->comment('無職期間');
            $table->text('employment_detail')->nullable()->comment('職歴の詳細');

            // カテゴリー5: 障害・医療情報
            $table->string('disability_physical', 5)->nullable()->comment('身体障害者手帳');
            $table->string('disability_physical_grade', 100)->nullable();
            $table->string('disability_mental', 5)->nullable()->comment('精神障害者保健福祉手帳');
            $table->string('disability_mental_grade', 100)->nullable();
            $table->string('disability_intellectual', 5)->nullable()->comment('療育手帳');
            $table->string('disability_intellectual_grade', 100)->nullable();
            $table->text('disability_detail')->nullable()->comment('障害者手帳の詳細');
            $table->text('hospital')->nullable()->comment('通院先');
            $table->text('medication')->nullable()->comment('服薬');

            // カテゴリー6: 生活状況
            $table->string('financial_status', 30)->nullable()->comment('経済状態');
            $table->text('financial_detail')->nullable()->comment('経済状態の詳細');
            $table->string('hikikomori', 5)->nullable()->comment('ひきこもり経験');
            $table->string('school_refusal', 5)->nullable()->comment('不登校経験');
            $table->string('bullying', 5)->nullable()->comment('いじめを受けた経験');

            // カテゴリー7: 支援管理
            $table->unsignedBigInteger('primary_counselor_id')->nullable();
            $table->text('cooperating_agencies')->nullable()->comment('連携機関');
            $table->unsignedBigInteger('support_status_id')->nullable();

            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス（SHOW CREATE TABLE の出現順）
            $table->unique('internal_id', 'clients_internal_id_unique');
            $table->index('primary_counselor_id', 'clients_primary_counselor_idx');
            $table->index('initial_consultation_date', 'clients_initial_date_idx');
            $table->index('created_at', 'clients_created_at_idx');
            $table->index('support_status_id', 'clients_support_status_idx');
            $table->index('updated_by', 'clients_updated_by_foreign');

            // 外部キー（作成順）
            $table->foreign('primary_counselor_id', 'clients_primary_counselor_id_foreign')
                ->references('id')->on('counselors')->nullOnDelete();
            $table->foreign('support_status_id', 'clients_support_status_id_foreign')
                ->references('id')->on('support_statuses')->nullOnDelete();
            $table->foreign('updated_by', 'clients_updated_by_foreign')
                ->references('id')->on('counselors')->nullOnDelete();
        });

        // CHECK 制約（全角文字を含む文字列値は既存マイグレーションから正確にコピー）
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_gender_check CHECK (gender IS NULL OR gender IN ('男', '女', 'その他'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_family_rel_check CHECK (family_relationship IN ('本人', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_education_level_check CHECK (education_level IS NULL OR education_level IN ('中学', '全日制高校', '定時制高校', '通信制高校', '高専', '専門学校', '大学', '短大', '大学院', 'その他'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_education_status_check CHECK (education_status IS NULL OR education_status IN ('卒業', '中退', '在学中', '休学中'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_employment_type_check CHECK (employment_type IS NULL OR employment_type IN ('正社員・正規職員', '契約社員・嘱託社員', 'パート・アルバイト', '派遣社員', 'その他・詳細不明'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_employment_hours_check CHECK (employment_hours IS NULL OR employment_hours IN ('週20時間以上', '週20時間未満', '不定期'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_employment_period_check CHECK (employment_period IS NULL OR employment_period IN ('有期雇用（3ヶ月未満）', '有期雇用（3～6ヶ月未満）', '有期雇用（6ヶ月～1年未満）', '有期雇用（1年以上）', '無期雇用'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_unemployment_period_check CHECK (unemployment_period IS NULL OR unemployment_period IN ('6ヶ月未満', '6ヶ月～1年', '1～3年', '3～5年', '5～10年', '10年以上'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_disability_phys_check CHECK (disability_physical IS NULL OR disability_physical IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_disability_mental_check CHECK (disability_mental IS NULL OR disability_mental IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_disability_intl_check CHECK (disability_intellectual IS NULL OR disability_intellectual IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_financial_check CHECK (financial_status IS NULL OR financial_status IN ('生活保護を受給している', '逼迫している', '特に困っていない'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_hikikomori_check CHECK (hikikomori IS NULL OR hikikomori IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_school_refusal_check CHECK (school_refusal IS NULL OR school_refusal IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_bullying_check CHECK (bullying IS NULL OR bullying IN ('あり', 'なし'))");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_initial_age_check CHECK (initial_age IS NULL OR (initial_age >= 0 AND initial_age <= 150))");
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
