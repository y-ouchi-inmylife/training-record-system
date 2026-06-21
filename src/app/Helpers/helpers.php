<?php

if (!function_exists('formatFileSize')) {
    /**
     * ファイルサイズを人間が読みやすい形式に変換
     *
     * @param int|null $bytes
     * @return string
     */
    function formatFileSize($bytes): string
    {
        if ($bytes === null || $bytes == 0) {
            return '0 B';
        }

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
