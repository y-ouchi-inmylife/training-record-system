/**
 * 未保存変更ガード
 * フォーム内の入力変更を検知し、保存せずに離脱しようとした際に警告を表示する。
 *
 * 使用例:
 *   new window.UnsavedChangesGuard({
 *       formSelector: '#counselingRecordForm',
 *       leaveLinkSelector: '.js-leave-link',
 *       message: '保存されていない変更があります。移動しますか？'
 *   }).init();
 */
export default class UnsavedChangesGuard {
    constructor(options) {
        this.formSelector = options.formSelector;
        this.leaveLinkSelector = options.leaveLinkSelector || '.js-leave-link';
        this.message = options.message || '保存されていない変更があります。移動しますか？';
        this.hasChanges = false;
    }

    init() {
        const form = document.querySelector(this.formSelector);
        if (!form) {
            return;
        }

        // フォーム内の全入力要素を監視
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach((el) => {
            // hidden や CSRF トークンは除外
            if (el.type === 'hidden') {
                return;
            }
            el.addEventListener('input', () => {
                this.hasChanges = true;
            });
            el.addEventListener('change', () => {
                this.hasChanges = true;
            });
        });

        // submit でフラグクリア（保存処理なので警告不要）
        form.addEventListener('submit', () => {
            this.hasChanges = false;
        });

        // ページ離脱時の警告（タブクローズ・ブラウザバック等）
        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // 離脱リンク（キャンセルボタン等）のクリック時警告
        const leaveLinks = document.querySelectorAll(this.leaveLinkSelector);
        leaveLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                if (this.hasChanges) {
                    if (!confirm(this.message)) {
                        e.preventDefault();
                    }
                }
            });
        });
    }
}
