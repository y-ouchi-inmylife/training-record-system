/**
 * トレーニー体重推移グラフの描画（S-1402 ダッシュボード）。
 *
 * Blade から `<canvas data-measurement-chart="...">` を配置し、その属性値に
 * JSON.stringify したチャートデータ（labels / tooltips / datasets）を載せる。
 * このスクリプトは DOM 読み込み後、該当する canvas をすべて拾って Chart.js
 * で折れ線を描く。
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
    CategoryScale,
    Tooltip,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
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
 * @param {{labels: string[], tooltips: string[], datasets: {data:(number|null)[]}[]}} data
 */
function renderChart(canvas, data) {
    const color = resolveLineColor(canvas);

    // 各セグメントは同じ色で描画。凡例は不要（線は 1 本の推移を表す）。
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
        spanGaps: false, // null は繋がない（分割の視覚化）
    }));

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // 分割セグメントを凡例で並べない
                tooltip: {
                    // タイトルに日時、本文に「体重 X.XX kg」を出す。
                    callbacks: {
                        title(items) {
                            const idx = items[0]?.dataIndex;
                            return idx != null ? data.tooltips[idx] : '';
                        },
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
                    ticks: {
                        // 計測があった日付のみをカテゴリとして並べているため、
                        // 密なときは自動で間引く（`autoSkip` は既定 true）。
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
            if (!data.labels || !data.datasets) return;
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
