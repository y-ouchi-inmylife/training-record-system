/**
 * マイページ登録案内（S-0307）の「文面をコピー」ボタンを動かす。
 *
 * 文面はサーバー側（ClientEmailRegistrationTokenController::buildCopyText）で
 * 組み立て、ボタンの data-copy-text 属性に載せる。JavaScript では画面上の
 * 文字を拾って組み立てず、この属性値をそのままクリップボードに書き込む
 * （画面の見た目と文面を独立させるため。screen-design.md S-0307 参照）。
 *
 * 押した結果が分かるように、成功時／失敗時ともボタンのラベルを一時的に
 * 変える。何も起きない状態にはしない（コピーできたと思って貼り付けに
 * 失敗するのを防ぐため）。
 */
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-copy-registration-message]');
    if (!button) return;

    const originalLabel = button.textContent;
    let resetTimer = null;

    // 押下後、指定ラベルへ一時的に切り替え、2 秒後に元に戻す。
    // 連打時は毎回タイマーをリセットする（表示がずれないように）。
    const flashLabel = (label) => {
        button.textContent = label;
        if (resetTimer) {
            window.clearTimeout(resetTimer);
        }
        resetTimer = window.setTimeout(() => {
            button.textContent = originalLabel;
            resetTimer = null;
        }, 2000);
    };

    button.addEventListener('click', async () => {
        const text = button.dataset.copyText ?? '';
        try {
            // navigator.clipboard は HTTPS もしくは localhost、かつユーザー操作起点でのみ動作する。
            // 使えない場合は失敗扱いにする（URL 文字列がページ上に残っているので、
            // トレーナーは手作業でコピーして貼り付けられる）。
            if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                throw new Error('clipboard API not available');
            }
            await navigator.clipboard.writeText(text);
            flashLabel('コピーしました');
        } catch (err) {
            console.error('文面のコピーに失敗しました:', err);
            flashLabel('コピーできませんでした');
        }
    });
});
