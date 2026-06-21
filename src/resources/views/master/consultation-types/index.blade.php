@extends('layouts.app')

@section('title', '相談内容マスタ')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">相談内容マスタ</h2>

            {{-- 新規追加フォーム --}}
            <div class="mb-5">
                <label class="form-label">新規追加</label>
                <form method="POST" action="{{ route('master.consultation-types.store') }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <div class="flex-grow-1">
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               inputmode="text"
                               placeholder="相談内容名を入力"
                               value="{{ old('name') }}" maxlength="50" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">追加</button>
                </form>
            </div>

            {{-- 登録済み一覧 --}}
            @if($types->isEmpty())
                <p class="text-muted mb-0">登録された相談内容がありません。</p>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 120px;">表示順</th>
                            <th>相談内容名</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($types as $index => $type)
                            <tr id="type-{{ $type->id }}">
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $index + 1 }}</span>
                                        <div class="d-flex gap-1">
                                            {{-- 上へ移動（先頭は無効化） --}}
                                            <form method="POST" action="{{ route('master.consultation-types.move-up', $type) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === 0 ? 'disabled' : '' }}>↑</button>
                                            </form>
                                            {{-- 下へ移動（末尾は無効化） --}}
                                            <form method="POST" action="{{ route('master.consultation-types.move-down', $type) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === $types->count() - 1 ? 'disabled' : '' }}>↓</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{-- 表示モード --}}
                                    <span class="type-name-display">{{ $type->name }}</span>
                                    {{-- 編集モード --}}
                                    <form method="POST" action="{{ route('master.consultation-types.update', $type) }}"
                                          class="type-name-edit d-none">
                                        @csrf
                                        @method('PUT')
                                        <div class="d-flex gap-2">
                                            <input type="text" name="name" class="form-control form-control-sm"
                                                   inputmode="text"
                                                   value="{{ $type->name }}" maxlength="50" required>
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
                                        <form method="POST" action="{{ route('master.consultation-types.destroy', $type) }}"
                                              class="d-inline"
                                              onsubmit="@if($type->counseling_records_count > 0) alert('この相談内容は相談記録で使用されているため削除できません。'); return false; @else return confirm('「{{ $type->name }}」を削除しますか？'); @endif">
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
            row.querySelector('.type-name-display').classList.add('d-none');
            row.querySelector('.type-name-edit').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });

    document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelector('.type-name-display').classList.remove('d-none');
            row.querySelector('.type-name-edit').classList.add('d-none');
            row.querySelector('.btn-edit').classList.remove('d-none');
        });
    });
</script>
@endpush
@endsection
