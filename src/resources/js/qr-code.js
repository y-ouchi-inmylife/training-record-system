import QRCode from 'qrcode';

/**
 * QR コードを canvas 要素に描く。
 *
 * 段階 4-2 の S-0305 発行モーダル・S-0307 印刷用ページの両方から使う。
 * 対象は `data-qr-url` 属性を持つすべての canvas 要素で、属性値の URL を
 * QR コード化して描画する。外部 CDN は使わず、ビルド成果物として提供する。
 *
 * 発行モーダルは Bootstrap の modal で非表示から表示に切り替わるが、描画は
 * 初期ロード時点で完了する（canvas 側のサイズは modal を開く前に確定するため
 * 表示時の見た目は変わらない）。
 */
function renderAllQrCodes() {
    const canvases = document.querySelectorAll('canvas[data-qr-url]');
    canvases.forEach((canvas) => {
        const url = canvas.getAttribute('data-qr-url');
        if (!url) return;

        // margin=1 は QR 仕様上の最小余白（quiet zone）。width は表示ピクセル。
        // 目視・読み取り両方に配慮した設定。
        QRCode.toCanvas(canvas, url, {
            width: parseInt(canvas.getAttribute('data-qr-size') || '220', 10),
            margin: 1,
            errorCorrectionLevel: 'M',
        }).catch((err) => {
            console.error('QR code render failed:', err);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAllQrCodes);
} else {
    renderAllQrCodes();
}
