<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * データベースをバックアップして R2 にアップロードするコマンド
 *
 * 設計書 docs/batch-design.md の B-0301 バックアップバッチに対応する。
 *
 * 処理:
 *   1. mysqldump の出力を openssl(AES-256-CBC + PBKDF2) にパイプして暗号化し、
 *      BACKUP_DIRECTORY 配下に保存する
 *   2. 作成した暗号化ファイルを Cloudflare R2 にアップロードする
 *   3. ローカルのバックアップファイルを削除する
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'データベースをバックアップして R2 にアップロードする';

    public function handle(): int
    {
        Log::info('[BackupDatabase] データベースバックアップを開始します');
        $this->info('データベースバックアップを開始します...');

        $dbName = env('DB_DATABASE');
        $backupDir = rtrim((string) env('BACKUP_DIRECTORY'), "/\\");
        $timestamp = now()->format('Ymd_His');
        $filename = "{$dbName}_{$timestamp}.sql.enc";
        $localPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            if ($backupDir === '' || ! is_dir($backupDir)) {
                throw new RuntimeException("BACKUP_DIRECTORY が存在しません: {$backupDir}");
            }

            // 1. mysqldump → openssl で暗号化バックアップを作成
            $this->dumpAndEncrypt($localPath);

            if (! file_exists($localPath)) {
                throw new RuntimeException("バックアップファイルが作成されませんでした: {$localPath}");
            }

            $fileSize = filesize($localPath);

            // 2. R2 にアップロード（ストリームで開いてメモリ消費を抑える）
            $stream = fopen($localPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException("バックアップファイルを開けませんでした: {$localPath}");
            }

            try {
                Storage::disk('r2')->put($filename, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            // 3. ローカルファイル削除（R2 アップロード成功後のみ）
            unlink($localPath);

            $sizeMb = round($fileSize / 1024 / 1024, 2);
            $message = "[BackupDatabase] {$filename} を R2 にアップロードしました（{$sizeMb} MB）";
            $this->info($message);
            Log::info($message);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            // 中間ファイルが残っていれば削除
            if (file_exists($localPath)) {
                @unlink($localPath);
            }

            $errorMessage = '[BackupDatabase] エラー: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, ['exception' => $e]);

            return Command::FAILURE;
        }
    }

    /**
     * mysqldump の出力を openssl にパイプして暗号化し、ファイルへ保存する
     */
    private function dumpAndEncrypt(string $outputPath): void
    {
        $mysqldumpCmd = [
            env('MYSQLDUMP_PATH'),
            '-h' . env('DB_HOST'),
            '-P' . env('DB_PORT'),
            '-u' . env('DB_USERNAME'),
            '-p' . env('DB_PASSWORD'),
            '--single-transaction',
            '--no-tablespaces',
            env('DB_DATABASE'),
        ];

        $opensslCmd = [
            env('OPENSSL_PATH'),
            'enc',
            '-aes-256-cbc',
            '-pbkdf2',
            '-pass', 'pass:' . env('BACKUP_ENCRYPTION_KEY'),
            '-out', $outputPath,
        ];

        $dumpSpec = [
            0 => ['pipe', 'r'], // 未使用
            1 => ['pipe', 'w'], // stdout → openssl の stdin に流す
            2 => ['pipe', 'w'], // stderr
        ];

        $opensslSpec = [
            0 => ['pipe', 'r'], // stdin（mysqldump の出力を受け取る）
            1 => ['pipe', 'w'], // stdout（-out 指定のため通常は空）
            2 => ['pipe', 'w'], // stderr
        ];

        $dumpProc = proc_open($mysqldumpCmd, $dumpSpec, $dumpPipes);
        if (! is_resource($dumpProc)) {
            throw new RuntimeException('mysqldump プロセスを起動できませんでした');
        }

        $opensslProc = proc_open($opensslCmd, $opensslSpec, $opensslPipes);
        if (! is_resource($opensslProc)) {
            // mysqldump 側を片付けてから例外を投げる
            foreach ($dumpPipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_terminate($dumpProc);
            proc_close($dumpProc);
            throw new RuntimeException('openssl プロセスを起動できませんでした');
        }

        // 不要なパイプを閉じる
        fclose($dumpPipes[0]);
        fclose($opensslPipes[1]);

        // mysqldump の stdout を読みつつ openssl の stdin に書き出す
        while (! feof($dumpPipes[1])) {
            $chunk = fread($dumpPipes[1], 65536);
            if ($chunk === false) {
                break;
            }
            if ($chunk !== '') {
                fwrite($opensslPipes[0], $chunk);
            }
        }
        fclose($dumpPipes[1]);
        fclose($opensslPipes[0]);

        // stderr を読み取る
        $dumpStderr = stream_get_contents($dumpPipes[2]) ?: '';
        $opensslStderr = stream_get_contents($opensslPipes[2]) ?: '';
        fclose($dumpPipes[2]);
        fclose($opensslPipes[2]);

        $dumpExit = proc_close($dumpProc);
        $opensslExit = proc_close($opensslProc);

        if ($dumpExit !== 0) {
            throw new RuntimeException("mysqldump が失敗しました（exit {$dumpExit}）: " . trim($dumpStderr));
        }
        if ($opensslExit !== 0) {
            throw new RuntimeException("openssl が失敗しました（exit {$opensslExit}）: " . trim($opensslStderr));
        }
    }
}
