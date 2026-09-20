/**
 * トレーニー体重推移グラフの描画（S-1402 ダッシュボード）。
 *
 * Blade から `<canvas data-measurement-chart="...">` を配置し、その属性値に
 * JSON.stringify したチャートデータ（datasets）を載せる。
 * このスクリプトは DOM 読み込み後、該当する canvas をすべて拾って Chart.js
 * で折れ線を描く。
 *
 * ## 横軸は時間軸（2026-09 変更、設計書 S-1402「横軸を時間軸に変更した経緯」参照）
 *
 * 従前はカテゴリ軸で計測日を等間隔に並べていたが、間隔が違う計測点が同じ幅で
 * 表示される問題があったため、時間軸（`type: 'time'`）に変更した。時間軸には
 * date アダプタが**別途必要**なため、`chartjs-adapter-date-fns` を import している
 * （import しないと `TypeError: Cannot read properties of undefined (reading 'time')`
 * のようなエラーで描画が失敗する。Chart.js 4.x の典型的なハマりどころ）。
 *
 * バンドルサイズ抑制のため、必要な Chart.js コンポーネントだけを register する
 * ツリーシェイク前提の import 形式を採る（Chart.js の推奨形。
 * 既存の `qrcode` と同じく npm 経由・ビルドに含める方針。docs/architecture.md §2-1 参照）。
 */
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    TimeScale,
    Tooltip,
    Filler,
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { format } from 'date-fns';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    TimeScale,
    Tooltip,
    Filler,
);

/**
 * canvas 要素と CSS 変数から線の色を決める。
 * `--c-brand-bright`（`resources/sass/client.scss` の :root で定義）を優先し、
 * 取得できない場合はハードコード値にフォールバックする。
 */
function resolveLineColor(canvas) {
    const style = getComputedStyle(canvas);
    const cssVar = style.getPropertyValue('--c-brand-bright').trim();
    return cssVar || '#2A4A94'; // Fallback: brand-bright と同値（client.scss $brand-bright）
}

/**
 * 単一の canvas に対してチャートを描画する。
 *
 * @param {HTMLCanvasElement} canvas
 * @param {{datasets: {data: {x: string, y: number}[]}[]}} data
 */
function renderChart(canvas, data) {
    const color = resolveLineColor(canvas);

    // 線は 1 本の連続した折れ線（線の分割は廃止）。凡例は不要。
    // spanGaps は使わない（null 埋めが無くなったため）。
    const datasets = data.datasets.map((seg) => ({
        data: seg.data,
        borderColor: color,
        backgroundColor: color,
        pointBackgroundColor: color,
        pointBorderColor: color,
        pointRadius: 3,
        pointHoverRadius: 5,
        borderWidth: 2,
        tension: 0, // 直線で結ぶ（スプライン補間しない）
    }));

    new Chart(canvas, {
        type: 'line',
        data: {
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    // タイトルは Chart.js の time スケールが `x` を自動整形する
                    // （下の scales.x.time.tooltipFormat で書式を指定）。
                    // 本文は「体重 X.XX kg」を出す。
                    callbacks: {
                        label(item) {
                            const v = item.parsed?.y;
                            return v == null ? '' : `体重 ${Number(v).toFixed(2)} kg`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    // 縦軸の範囲は Chart.js の既定（データに合わせた自動調整）に任せる。
                    // 目盛りは詰まりすぎないように maxTicksLimit で本数を抑え、
                    // 各目盛りに単位「kg」を付ける。縦軸ラベルには「体重」を表示し、
                    // セクションの eyebrow は置かない（2026-09 変更。詳細は
                    // screen-design.md S-1402「体重推移」節参照）。
                    // `rotation: 0` で横書きにする理由：Chart.js の `scales.y.title` の
                    // 既定は文字列を 90° 回転させる（欧文向け）。日本語は文字ごと
                    // 回転させない縦書きが正しい表現だが、Chart.js は文字全体を回転
                    // させる仕様のため、日本語では 1 文字ずつ横倒しになって読みにくく
                    // なる。回転を止めて素直な横書きで表示する
                    // （将来この設定を見て「回転させたほうが体裁が良い」と考えて
                    // 既定に戻さないよう、この経緯をここに残す）。
                    title: { display: true, text: '体重', rotation: 0 },
                    ticks: {
                        maxTicksLimit: 5,
                        // 小数点以下 1 桁で揃える。実測値は 2 桁まで持つが軸目盛りは
                        // 1 桁で十分（詳細な値はツールチップ側に 2 桁で出す）。
                        callback(value) {
                            return `${Number(value).toFixed(1)} kg`;
                        },
                    },
                },
                x: {
                    // 時間軸。data の各点は {x: 'YYYY-MM-DDTHH:MM:SS' (ローカル time), y: 体重}。
                    // date-fns アダプタが naive な ISO 8601 文字列を**ローカル時間として解釈**する
                    // ため、コントローラ側で UTC の 'Z' や '+HH:MM' オフセットは付けない。
                    type: 'time',
                    time: {
                        // ツールチップ表示用の書式（date-fns のトークン）。
                        // 「2026/9/14 08:00」の形。yyyy=4桁年、M=1〜2桁月、d=1〜2桁日、HH=2桁時、mm=2桁分。
                        // 秒は表示しない（分精度で十分）。
                        tooltipFormat: 'yyyy/M/d HH:mm',
                        // 目盛りの粒度を**日単位に固定**する（設計書 S-1402「横軸の目盛りを
                        // 日単位に固定した経緯」参照）。当初は unit 未指定で Chart.js の
                        // 自動選定に任せていたが、データ点が少ないと時単位が選ばれ
                        // 「12PM / 6PM / 12AM…」の目盛りが並び日付が読めなくなったため、
                        // 日単位に固定した。**time.unit は残しておく必要がある**：これは
                        // tick 生成の粒度を決める設定で、外すと時単位に戻り日付が読めない
                        // 問題が再発する（下の ticks.callback は「生成されたティックの
                        // 描画方法」を変えるだけで、生成の粒度には影響しない）。
                        // 点の位置は raw x の値（時刻を含む日時）で決まる Chart.js の仕様
                        // どおりのため、同じ日に朝・夕の 2 回計測した 2 点は目盛りが日単位
                        // でも時刻分だけ横に離れて表示される。
                        //
                        // **displayFormats.day は指定しない**：下の ticks.callback で
                        // 描画書式を配列で返しており、`callback` を指定すると `displayFormats`
                        // は Chart.js 内部で無視される仕様のため。残しておくと「使われている」
                        // と誤解を招くので削除した（設計書「横軸ラベルを 2 行にした経緯」参照）。
                        unit: 'day',
                    },
                    ticks: {
                        // 目盛りが密なときは自動で間引く。ラベルは回転させない。
                        autoSkip: true,
                        maxRotation: 0,
                        // 日付と時刻を 2 行に分けて表示する（設計書 S-1402「横軸ラベルを
                        // 2 行にした経緯」参照）。当初は displayFormats.day を「M/d H:mm」に
                        // して 1 行で「9/15 0:00」を出していたが、ラベルが横に長く読みにくかった
                        // ため、日付（M/d）と時刻（H:mm）で改行する形にした。Chart.js は
                        // callback から配列を返すと各要素を別の行として描画する。
                        //
                        // 書式は date-fns の format を直接使う：既に chartjs-adapter-date-fns
                        // 経由で date-fns が入っており、アダプタと同じ書式トークンで表記を
                        // 揃えられるため（素の Date.toLocaleString より意図が明確）。
                        // `value` はタイムスタンプ（ミリ秒）で渡ってくる Chart.js の仕様。
                        //
                        // ツールチップには影響しない：`tooltipFormat: 'yyyy/M/d HH:mm'` は
                        // 別系統で有効なままで、ツールチップは「2026/9/15 08:00」の 1 行表示。
                        callback(value) {
                            const d = new Date(value);
                            return [format(d, 'M/d'), format(d, 'H:mm')];
                        },
                    },
                },
            },
        },
    });
}

function renderAllMeasurementCharts() {
    const canvases = document.querySelectorAll('canvas[data-measurement-chart]');
    canvases.forEach((canvas) => {
        const raw = canvas.getAttribute('data-measurement-chart');
        if (!raw) return;
        try {
            const data = JSON.parse(raw);
            if (!data.datasets || data.datasets.length === 0) return;
            renderChart(canvas, data);
        } catch (err) {
            console.error('measurement chart render failed:', err);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAllMeasurementCharts);
} else {
    renderAllMeasurementCharts();
}
