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
                        // time.unit は指定しない（Chart.js の自動選定に任せる。
                        // データの範囲に応じて日単位・月単位などが選ばれる）。
                    },
                    ticks: {
                        // 目盛りが密なときは自動で間引く。ラベルは回転させない。
                        autoSkip: true,
                        maxRotation: 0,
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
