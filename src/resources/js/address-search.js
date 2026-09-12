/**
 * 郵便番号から住所を検索して都道府県・市区町村・町名番地を自動入力する。
 *
 * トレーナー側（clients/_form.blade.php）とクライアント側（client/setup/index.blade.php）で
 * 共用する。エラーの通知先は Blade 側に配置された `#address-search-message` 要素の
 * 有無で切り替える：
 * - 要素が存在する（クライアント側）：画面内メッセージとして表示する
 * - 要素が存在しない（トレーナー側）：既存挙動の alert() を表示する
 *
 * Blade 側の `<button onclick="searchAddress()">` から呼ばれるため、
 * グローバルスコープ（window）に公開する。
 */
function notifyAddressSearch(message, isError = true) {
    const target = document.getElementById('address-search-message');
    if (target) {
        target.textContent = message;
        // Bootstrap の form-text 色トグル（alert なしで視覚的な失敗を示す）
        target.classList.toggle('text-danger', isError);
        target.classList.toggle('text-success', !isError);
        return;
    }
    // フォールバック（トレーナー側は従来どおり alert）
    if (isError) {
        alert(message);
    }
}

function searchAddress() {
    const postalCode = document.getElementById('postal_code').value.replace(/[^0-9]/g, '');
    if (postalCode.length !== 7) {
        notifyAddressSearch('郵便番号を7桁で入力してください。');
        return;
    }

    // 開始時に前回のメッセージをクリア
    notifyAddressSearch('', false);

    const btn = document.getElementById('btn-search-address');
    btn.disabled = true;
    btn.textContent = '検索中…';

    fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.results) {
                const result = data.results[0];
                document.getElementById('address1').value = result.address1;
                document.getElementById('address2').value = result.address2;
                document.getElementById('address3').value = result.address3;
                notifyAddressSearch('住所を自動入力しました。', false);
            } else {
                notifyAddressSearch('該当する住所が見つかりませんでした。');
            }
        })
        .catch(() => {
            notifyAddressSearch('住所検索に失敗しました。');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '検索';
        });
}

window.searchAddress = searchAddress;
