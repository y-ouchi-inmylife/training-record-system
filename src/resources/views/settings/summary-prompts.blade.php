@extends('layouts.app')

@section('title', '要約プロンプト')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 700px;">
                <h2 class="mb-0">要約プロンプト</h2>
                <div class="d-flex gap-2">
                    <button type="submit" form="summaryPromptForm" class="btn btn-success">更新</button>
                </div>
            </div>

            {{-- プロンプト編集ガイド（利用者向け説明） --}}
            <div class="alert alert-info" style="max-width: 700px;">
                <p class="mb-2">このプロンプトは、AI が音声記録を要約する際の「指示書」です。ここに書いた内容がそのまま AI に渡され、要約の観点や形式を決めます。</p>
                <p class="mb-0">【要約項目】や【出力形式】などの見出しは、AI に分かりやすく指示するための文章です（システムが認識する特別なキーワードではありません）。項目を消してもシステムは止まりませんが、その観点は要約に反映されなくなります。要約してほしい観点は項目として残す・追加するのがコツです。</p>
            </div>

            <form id="summaryPromptForm" method="POST" action="{{ route('settings.summary-prompts.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <textarea class="form-control @error('current_prompt') is-invalid @enderror"
                              id="current_prompt"
                              name="current_prompt"
                              inputmode="text"
                              rows="12"
                              style="font-family: monospace; max-width: 700px;">{{ old('current_prompt', $currentPrompt) }}</textarea>

                    @error('current_prompt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    {{-- 文字数カウンタ（上限超過時は赤表示。入力自体はブロックしない） --}}
                    <div class="form-text text-muted" id="prompt-char-counter" style="max-width: 700px;"></div>
                </div>

                {{-- プリセットボタン --}}
                <div class="mb-3" style="max-width: 700px;">
                    <label class="form-label">プリセット</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary" id="btn-preset-counseling">
                            心理カウンセリング用
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-preset-employment">
                            就労支援用
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-reset-saved">
                            現在設定中のプロンプトに戻す
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('current_prompt');
    var savedPrompt = @json($currentPrompt);

    // 文字数カウンタの上限（サーバー側バリデーション max:5000 と一致させる）
    const MAX = 5000;
    var counter = document.getElementById('prompt-char-counter');

    function updateCharCounter() {
        var length = textarea.value.length;
        counter.textContent = length + ' / ' + MAX;
        // 上限超過時は赤く表示（入力はブロックしない）
        var over = length > MAX;
        counter.classList.toggle('text-danger', over);
        counter.classList.toggle('text-muted', !over);
    }

    // 入力に追従（プリセット挿入時も setTextareaValue が input イベントを発火するため更新される）
    textarea.addEventListener('input', updateCharCounter);
    // 初期表示時にも現在の文字数を反映
    updateCharCounter();

    // プリセットは system_settings 由来の値を Controller から受け取る（ハードコードしない）。構造は {counseling, employment}
    var presets = @json($presets);

    // プリセットボタンで値を差し替えた後、UnsavedChangesGuard に変更を検知させるため input イベントを発火する
    function setTextareaValue(value) {
        textarea.value = value;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.getElementById('btn-preset-counseling').addEventListener('click', function() {
        // 誤クリックで編集中の内容が失われるのを防ぐため、適用前に確認する
        if (!confirm('変更内容が破棄されます。プリセットを適用しますか？')) return;
        setTextareaValue(presets.counseling);
    });

    document.getElementById('btn-preset-employment').addEventListener('click', function() {
        // 誤クリックで編集中の内容が失われるのを防ぐため、適用前に確認する
        if (!confirm('変更内容が破棄されます。プリセットを適用しますか？')) return;
        setTextareaValue(presets.employment);
    });

    document.getElementById('btn-reset-saved').addEventListener('click', function() {
        setTextareaValue(savedPrompt);
    });

    // 未保存変更警告
    new window.UnsavedChangesGuard({
        formSelector: '#summaryPromptForm',
        leaveLinkSelector: '.js-leave-link'
    }).init();
});
</script>
@endpush
