@extends('layouts.app')

@section('title', 'フェーズマスタ')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">フェーズマスタ</h2>

            {{-- 新規追加フォーム --}}
            <div class="mb-5">
                <label class="form-label">新規追加</label>
                <form method="POST" action="{{ route('master.phases.store') }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <div class="flex-grow-1">
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               inputmode="text"
                               placeholder="フェーズ名を入力"
                               value="{{ old('name') }}" maxlength="100" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">追加</button>
                </form>
            </div>

            {{-- 登録済み一覧 --}}
            @if($phases->isEmpty())
                <p class="text-muted mb-0">登録されたフェーズがありません。</p>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 120px;">表示順</th>
                            <th>フェーズ名</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($phases as $index => $phase)
                            <tr id="phase-{{ $phase->id }}">
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $index + 1 }}</span>
                                        <div class="d-flex gap-1">
                                            {{-- 上へ移動（先頭は無効化） --}}
                                            <form method="POST" action="{{ route('master.phases.move-up', $phase) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === 0 ? 'disabled' : '' }}>↑</button>
                                            </form>
                                            {{-- 下へ移動（末尾は無効化） --}}
                                            <form method="POST" action="{{ route('master.phases.move-down', $phase) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === $phases->count() - 1 ? 'disabled' : '' }}>↓</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{-- 表示モード --}}
                                    <span class="phase-name-display">{{ $phase->name }}</span>
                                    {{-- 編集モード --}}
                                    <form method="POST" action="{{ route('master.phases.update', $phase) }}"
                                          class="phase-name-edit d-none">
                                        @csrf
                                        @method('PUT')
                                        <div class="d-flex gap-2">
                                            <input type="text" name="name" class="form-control form-control-sm"
                                                   inputmode="text"
                                                   value="{{ $phase->name }}" maxlength="100" required>
                                            <button type="submit" class="btn btn-sm btn-success">保存</button>
                                            <button type="button" class="btn btn-sm btn-secondary btn-cancel-edit">取消</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        {{-- 編集 --}}
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit">編集</button>

                                        {{-- 削除 --}}
                                        <form method="POST" action="{{ route('master.phases.destroy', $phase) }}"
                                              class="d-inline"
                                              onsubmit="@if($phase->counseling_records_count > 0) alert('このフェーズはトレーニング記録で使用されているため削除できません。'); return false; @else return confirm('「{{ $phase->name }}」を削除しますか？'); @endif">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script>
    // インライン編集の切り替え
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelector('.phase-name-display').classList.add('d-none');
            row.querySelector('.phase-name-edit').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });

    document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelector('.phase-name-display').classList.remove('d-none');
            row.querySelector('.phase-name-edit').classList.add('d-none');
            row.querySelector('.btn-edit').classList.remove('d-none');
        });
    });
</script>
@endpush
@endsection
