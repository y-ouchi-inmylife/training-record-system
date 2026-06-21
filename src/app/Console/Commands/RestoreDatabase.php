<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * R2 のバックアップファイルからデータベースをリストアするコマンド
 *
 * 設計書 docs/batch-design.md の B-0302 リストアバッチに対応する。
 *
 * 処理:
 *   1. リストア対象ファイルと DB 名を表示して Y/N で確認を求める
 *   2. R2 のバケットから指定されたバックアップファイルをダウンロードし、
 *      BACKUP_DIRECTORY 配下に保存する
 *   3. openssl で復号した出力を mysql にパイプして DB を復元する
 *   4. ローカルにダウンロードしたバックアップファイルを削除する
 */
class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {filename : R2 上のバックアップファイル名}';

    protected $description = 'R2 のバックアップファイルからデータベースをリストアする';

    public function handle(): int
    {
        $filename = (string) $this->argument('filename');

        // ファイル名のサニタイズ（パストラバーサル等の防止）
        if (! preg_match('/^[a-zA-Z0-9_.-]+\.sql\.enc$/', $filename)) {
            $errorMessage = "[RestoreDatabase] エラー: 不正なファイル名です: {$filename}";
            $this->error($errorMessage);
            Log::error($errorMessage);

            return Command::FAILURE;
        }

        $dbName = env('DB_DATABASE');
        $backupDir = rtrim((string) env('BACKUP_DIRECTORY'), "/\\");
        $localPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // 1. Y/N 確認
        $confirmed = $this->confirm(
            "ファイル '{$filename}' でデータベース '{$dbName}' をリストアします。既存のデータは上書きされます。続行しますか？"
        );

        if (! $confirmed) {
            $message = '[RestoreDatabase] 処理を中止しました';
            $this->info($message);
            Log::info($message);

            return Command::SUCCESS;
        }

        Log::info("[RestoreDatabase] {$filename} からリストアを開始します");
        $this->info("{$filename} からリストアを開始します...");

        try {
            if ($backupDir === '' || ! is_dir($backupDir)) {
                throw new RuntimeException("BACKUP_DIRECTORY が存在しません: {$backupDir}");
            }

            // 2. R2 からダウンロード
            $this->downloadFromR2($filename, $localPath);

            if (! file_exists($localPath)) {
                throw new RuntimeException("ダウンロードしたファイルが見つかりません: {$localPath}");
            }

            // 3. openssl で復号 → mysql で実行
            $this->decryptAndRestore($localPath);

            // 4. ローカルファイル削除（mysql 成功後のみ）
            unlink($localPath);

            $message = "[RestoreDatabase] {$filename} からデータベース {$dbName} をリストアしました";
            $this->info($message);
            Log::info($message);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            // 失敗時はデバッグ用にローカルファイルを残すため削除しない
            $errorMessage = '[RestoreDatabase] エラー: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, ['exception' => $e]);

            return Command::FAILURE;
        }
    }

    /**
     * R2 から指定ファイルをストリームでダウンロードしてローカルへ保存する
     */
    private function downloadFromR2(string $filename, string $localPath): void
    {
        $disk = Storage::disk('r2');

        if (! $disk->exists($filename)) {
            throw new RuntimeException("R2 にファイルが存在しません: {$filename}");
        }

        $remoteStream = $disk->readStream($filename);
        if ($remoteStream === false || $remoteStream === null) {
            throw new RuntimeException("R2 ファイルのストリームを開けませんでした: {$filename}");
        }

        $localStream = fopen($localPath, 'wb');
        if ($localStream === false) {
            if (is_resource($remoteStream)) {
                fclose($remoteStream);
            }
            throw new RuntimeException("ローカルファイルを開けませんでした: {$localPath}");
        }

        try {
            if (stream_copy_to_stream($remoteStream, $localStream) === false) {
                throw new RuntimeException("R2 からのダウンロードに失敗しました: {$filename}");
            }
        } finally {
            if (is_resource($localStream)) {
                fclose($localStream);
            }
            if (is_resource($remoteStream)) {
                fclose($remoteStream);
            }
        }
    }

    /**
     * openssl で復号した出力を mysql にパイプして DB を復元する
     */
    private function decryptAndRestore(string $inputPath): void
    {
        $opensslCmd = [
            env('OPENSSL_PATH'),
            'enc',
            '-aes-256-cbc',
            '-pbkdf2',
            '-d',
            '-pass', 'pass:' . env('BACKUP_ENCRYPTION_KEY'),
            '-in', $inputPath,
        ];

        $mysqlCmd = [
            env('MYSQL_PATH'),
            '-h' . env('DB_HOST'),
            '-P' . env('DB_PORT'),
            '-u' . env('DB_USERNAME'),
            '-p' . env('DB_PASSWORD'),
            env('DB_DATABASE'),
        ];

        $opensslSpec = [
            0 => ['pipe', 'r'], // 未使用（-in でファイル指定）
            1 => ['pipe', 'w'], // stdout → mysql の stdin に流す
            2 => ['pipe', 'w'], // stderr
        ];

        $mysqlSpec = [
            0 => ['pipe', 'r'], // stdin（openssl の出力を受け取る）
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $opensslProc = proc_open($opensslCmd, $opensslSpec, $opensslPipes);
        if (! is_resource($opensslProc)) {
            throw new RuntimeException('openssl プロセスを起動できませんでした');
        }

        $mysqlProc = proc_open($mysqlCmd, $mysqlSpec, $mysqlPipes);
        if (! is_resource($mysqlProc)) {
            foreach ($opensslPipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_terminate($opensslProc);
            proc_close($opensslProc);
            throw new RuntimeException('mysql プロセスを起動できませんでした');
        }

        // 不要なパイプを閉じる
        fclose($opensslPipes[0]);
        fclose($mysqlPipes[1]);

        // openssl の stdout を読みつつ mysql の stdin に書き出す
        while (! feof($opensslPipes[1])) {
            $chunk = fread($opensslPipes[1], 65536);
            if ($chunk === false) {
                break;
            }
            if ($chunk !== '') {
                fwrite($mysqlPipes[0], $chunk);
            }
        }
        fclose($opensslPipes[1]);
        fclose($mysqlPipes[0]);

        // stderr を読み取る
        $opensslStderr = stream_get_contents($opensslPipes[2]) ?: '';
        $mysqlStderr = stream_get_contents($mysqlPipes[2]) ?: '';
        fclose($opensslPipes[2]);
        fclose($mysqlPipes[2]);

        $opensslExit = proc_close($opensslProc);
        $mysqlExit = proc_close($mysqlProc);

        if ($opensslExit !== 0) {
            throw new RuntimeException("openssl が失敗しました（exit {$opensslExit}）: " . trim($opensslStderr));
        }
        if ($mysqlExit !== 0) {
            throw new RuntimeException("mysql が失敗しました（exit {$mysqlExit}）: " . trim($mysqlStderr));
        }
    }
}
