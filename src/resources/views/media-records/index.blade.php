@extends('layouts.app')

@section('title', 'メディア一覧')

@section('content')
<div class="container">
    <h2 class="mb-3">メディア一覧</h2>

    {{-- 登録者フィルタ --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <label for="trainer-filter" class="form-label mb-0 text-nowrap">登録者:</label>
        <select id="trainer-filter" class="form-select" style="width: auto;">
            <option value="all" {{ $selectedTrainerId == 'all' ? 'selected' : '' }}>全員</option>
            @foreach($trainers as $trainer)
                <option value="{{ $trainer->id }}" {{ $selectedTrainerId == $trainer->id ? 'selected' : '' }}>
                    {{ $trainer->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- メディアグリッド --}}
    @if($mediaRecords->isEmpty())
        <div class="alert alert-info">
            データがありません。メディア登録から追加してください。
        </div>
    @else
        {{-- レスポンシブグリッド（2列〜6列）。サムネイル無しのプレースホルダ表示。
             サムネイル実装時は .ratio 内を <img> に差し替える素朴な構造。 --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
            @foreach($mediaRecords as $media)
                <div class="col">
                    <div class="card h-100">
                        <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                            <span class="text-muted">
                                {{ $media->type === \App\Models\MediaRecord::TYPE_PHOTO ? '写真' : '動画' }}
                            </span>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="text-muted">{{ $media->created_at->format('Y/m/d H:i') }}</div>
                            <div class="text-truncate" title="{{ $media->display_title }}">{{ $media->display_title }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $mediaRecords->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 登録者フィルタの変更で trainer_id クエリを差し替えて再読み込み（ページはリセット）
    const trainerFilter = document.getElementById('trainer-filter');
    if (trainerFilter) {
        trainerFilter.addEventListener('change', function() {
            const value = this.value;
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            url.searchParams.set('trainer_id', value);
            window.location.href = url.toString();
        });
    }
});
</script>
@endpush
