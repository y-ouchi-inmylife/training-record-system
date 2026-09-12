/**
 * 郵便番号から住所を検索して都道府県・市区町村・町名番地を自動入力する。
 *
 * 元は clients/_form.blade.php の inline <script> に書かれていたものを、
 * 段階 4-1 の初回設定画面（クライアント側）でも同じ処理を流用するために
 * 独立ファイルに切り出した。処理内容・エラー時の挙動・alert() の使用は
 * 移動時点のまま。
 *
 * Blade 側の `<button onclick="searchAddress()">` から呼ばれるため、
 * グローバルスコープ（window）に公開する。
 */
function searchAddress() {
    const postalCode = document.getElementById('postal_code').value.replace(/[^0-9]/g, '');
    if (postalCode.length !== 7) {
        alert('郵便番号を7桁で入力してください。');
        return;
    }

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
            } else {
                alert('該当する住所が見つかりませんでした。');
            }
        })
        .catch(() => {
            alert('住所検索に失敗しました。');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '検索';
        });
}

window.searchAddress = searchAddress;
