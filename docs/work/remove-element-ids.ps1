#Requires -Version 5.1
# screen-design.md から要素IDを廃止するスクリプト
# 1. 画面要素表から「要素ID」列を削除（削除済み行は行ごと削除）
# 2. バリデーション表の「要素ID」列を「要素」列に置換し、値を要素名に変換
# 3. テーブル行・本文中のインライン要素ID参照を要素名に変換

$ErrorActionPreference = 'Stop'

$Path = 'c:\Users\y-ouchi\workspace\dev\counseling-record-system\docs\screen-design.md'
$lines = [System.IO.File]::ReadAllLines($Path, [System.Text.Encoding]::UTF8)

# ============================================================
# Pass 1: ID → 要素名 マップを構築
# ============================================================
$idToName = @{}
$inElementTable = $false

for ($i = 0; $i -lt $lines.Length; $i++) {
    $line = $lines[$i]

    if ($line -match '^\|\s*要素ID\s*\|\s*要素名\s*\|') {
        $inElementTable = $true
        continue
    }
    if ($inElementTable -and $line -match '^\|[\s\-]+\|') {
        continue
    }
    if ($inElementTable) {
        if ($line -match '^\|\s*(S\d{4}-E\d{2})\s*\|\s*([^|]+?)\s*\|') {
            $id = $matches[1]
            $name = $matches[2].Trim()
            if ($name -ne '—') {
                $idToName[$id] = $name
            }
        }
        if (-not $line.StartsWith('|')) {
            $inElementTable = $false
        }
    }
}

Write-Host "ID→要素名マッピング件数: $($idToName.Count)"

# ============================================================
# 補助関数：ID表現を要素名に解決
# ============================================================
function Resolve-IdExpr($idExpr) {
    # "S0301-E02" / "S0301-E02, E03" / "E02" などを要素名に変換
    $parts = $idExpr -split '\s*,\s*'
    $first = $parts[0]
    $prefix = $null
    if ($first -match '^(S\d{4}-)E\d{2}$') { $prefix = $matches[1] }

    $names = New-Object System.Collections.Generic.List[string]
    foreach ($p in $parts) {
        $p = $p.Trim()
        if ($p -match '^E\d{2}$' -and $prefix) { $p = $prefix + $p }
        if ($idToName.ContainsKey($p)) {
            $names.Add($idToName[$p])
        } else {
            $names.Add($p)
        }
    }
    return ($names -join '、')
}

# ============================================================
# 補助関数：行内のインライン参照を置換
# ============================================================
function Replace-InlineRefs($line) {
    $new = $line

    # （Sxxxx-Exxと同値）→ （要素名と同値）
    $new = [regex]::Replace($new, '（(S\d{4}-E\d{2})と同値）', {
        param($m)
        $id = $m.Groups[1].Value
        if ($idToName.ContainsKey($id)) { "（$($idToName[$id])と同値）" } else { $m.Value }
    })

    # （Sxxxx-Exx 参照）→ （要素名 参照）
    $new = [regex]::Replace($new, '（(S\d{4}-E\d{2})\s+参照）', {
        param($m)
        $id = $m.Groups[1].Value
        if ($idToName.ContainsKey($id)) { "（$($idToName[$id]) 参照）" } else { $m.Value }
    })

    # （Sxxxx-Exx）単独 → 削除（前後文脈で要素が分かる前提）
    $new = [regex]::Replace($new, '（S\d{4}-E\d{2}）', '')

    # Sxxxx-Exxと一致 → 要素名と一致
    $new = [regex]::Replace($new, '(S\d{4}-E\d{2})と一致', {
        param($m)
        $id = $m.Groups[1].Value
        if ($idToName.ContainsKey($id)) { "$($idToName[$id])と一致" } else { $m.Value }
    })

    # 残存ID → マップにあれば要素名に置換（最後の保険）
    $new = [regex]::Replace($new, 'S\d{4}-E\d{2}', {
        param($m)
        $id = $m.Value
        if ($idToName.ContainsKey($id)) { $idToName[$id] } else { $m.Value }
    })

    return $new
}

# ============================================================
# Pass 2: 構造変換 + インライン参照置換
# ============================================================
$out = New-Object System.Collections.Generic.List[string]
$mode = $null  # $null / 'element' / 'validation'

for ($i = 0; $i -lt $lines.Length; $i++) {
    $line = $lines[$i]

    # 画面要素表のヘッダ検出
    if ($line -match '^\|\s*要素ID\s*\|\s*要素名\s*\|\s*種類\s*\|\s*必須\s*\|\s*説明\s*\|') {
        $mode = 'element'
        $out.Add('| 要素名 | 種類 | 必須 | 説明 |')
        continue
    }
    # バリデーション表のヘッダ検出
    if ($line -match '^\|\s*要素ID\s*\|\s*チェック内容\s*\|\s*エラーメッセージ\s*\|') {
        $mode = 'validation'
        $out.Add('| 要素 | チェック内容 | エラーメッセージ |')
        continue
    }

    # セパレータ行
    if ($mode -ne $null -and $line -match '^\|[\s\-]+\|[\s\-]+\|') {
        if ($mode -eq 'element') {
            $out.Add('|--------|------|------|------|')
        } else {
            $out.Add('|------|-------------|-----------------|')
        }
        continue
    }

    # データ行（画面要素）
    if ($mode -eq 'element' -and $line.StartsWith('|')) {
        # 削除済み行（先頭がID、残りすべて—）を削除
        if ($line -match '^\|\s*S\d{4}-E\d{2}\s*\|\s*—\s*\|\s*—\s*\|\s*—\s*\|') {
            continue
        }
        # 先頭の "| ID " を削除
        $new = $line -replace '^\|\s*S\d{4}-E\d{2}\s*\|', '|'
        # 残りに含まれるインライン参照も置換
        $new = Replace-InlineRefs $new
        $out.Add($new)
        continue
    }

    # データ行（バリデーション）
    if ($mode -eq 'validation' -and $line.StartsWith('|')) {
        if ($line -match '^\|\s*(S\d{4}-E\d{2}(?:\s*,\s*E\d{2})*)\s*\|(.*)$') {
            $idExpr = $matches[1]
            $rest = $matches[2]
            $name = Resolve-IdExpr $idExpr
            # restに含まれるインライン参照も置換
            $rest = Replace-InlineRefs $rest
            $out.Add("| $name |$rest")
            continue
        }
        # IDがない行（— や 説明行）— インライン参照だけ処理
        $out.Add((Replace-InlineRefs $line))
        continue
    }

    # テーブル外
    if ($mode -ne $null -and -not $line.StartsWith('|')) {
        $mode = $null
    }

    # 本文中のインライン参照
    if ($line -match 'S\d{4}-E\d{2}') {
        $out.Add((Replace-InlineRefs $line))
        continue
    }

    $out.Add($line)
}

# ============================================================
# 書き出し
# ============================================================
[System.IO.File]::WriteAllLines($Path, $out, (New-Object System.Text.UTF8Encoding $false))
Write-Host "書き出し完了: $Path"
Write-Host "出力行数: $($out.Count) (元: $($lines.Length))"
