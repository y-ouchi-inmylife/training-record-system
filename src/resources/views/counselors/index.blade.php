@extends('layouts.app')

@section('title', 'カウンセラー管理')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>カウンセラー管理</h2>
        <a href="{{ route('counselors.create') }}" class="btn btn-primary">新規登録</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 120px;">表示順</th>
                        <th>ログインID</th>
                        <th>名前</th>
                        <th>権限</th>
                        <th>最終ログイン</th>
                        <th>登録日</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($counselors as $index => $counselor)
                        <tr>
                            <td>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ $index + 1 }}</span>
                                    <div class="d-flex gap-1">
                                        {{-- 上へ移動（先頭は無効化） --}}
                                        <form method="POST" action="{{ route('counselors.move-up', $counselor) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === 0 ? 'disabled' : '' }}>↑</button>
                                        </form>
                                        {{-- 下へ移動（末尾は無効化） --}}
                                        <form method="POST" action="{{ route('counselors.move-down', $counselor) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" {{ $index === count($counselors) - 1 ? 'disabled' : '' }}>↓</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $counselor->login_id }}</td>
                            <td>
                                {{ $counselor->name }}
                                @if($counselor->id === auth()->id())
                                    <span class="badge bg-secondary">自分</span>
                                @endif
                                @if(!$counselor->is_active)
                                    <span class="badge bg-secondary">無効</span>
                                @endif
                                @if($counselor->is_locked)
                                    <span class="badge bg-danger">ロック</span>
                                @endif
                            </td>
                            <td>
                                @if($counselor->isSystemAdmin())
                                    <span class="badge bg-secondary">{{ $counselor->role_display_name }}</span>
                                @elseif($counselor->role === 'admin')
                                    <span class="badge bg-secondary">{{ $counselor->role_display_name }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $counselor->role_display_name }}</span>
                                @endif
                            </td>
                            <td>{{ $counselor->last_login_at?->format('Y/m/d H:i') ?: '未ログイン' }}</td>
                            <td>{{ $counselor->created_at?->format('Y/m/d') }}</td>
                            <td class="text-end">
                                @if($counselor->isSystemAdmin())
                                    <span class="text-muted">—</span>
                                @else
                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                    {{-- 編集・PWリセット・ロック解除・無効化/有効化・削除（自分自身には非表示） --}}
                                    @if($counselor->id !== Auth::id())
                                        <a href="{{ route('counselors.edit', $counselor) }}" class="btn btn-outline-primary btn-sm">編集</a>
                                        <a href="{{ route('counselors.reset-password', $counselor) }}" class="btn btn-outline-secondary btn-sm">PWリセット</a>
                                        @if($counselor->is_locked)
                                            <form method="POST" action="{{ route('counselors.unlock', $counselor) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('「{{ $counselor->name }}」のロックを解除しますか？')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">ロック解除</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('counselors.toggle-active', $counselor) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('「{{ $counselor->name }}」を{{ $counselor->is_active ? '無効化' : '有効化' }}しますか？')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                {{ $counselor->is_active ? '無効化' : '有効化' }}
                                            </button>
                                        </form>
                                        @php
                                            $isOnlyAdmin = $counselor->role === 'admin' && $adminCount <= 1;
                                            $hasRecords = $counselor->counseling_records_as_counselor1_count + $counselor->counseling_records_as_counselor2_count > 0;
                                            $hasPrimaryClients = $counselor->primary_clients_count > 0;
                                        @endphp
                                        <form method="POST" action="{{ route('counselors.destroy', $counselor) }}"
                                              class="d-inline"
                                              onsubmit="@if($isOnlyAdmin) alert('管理者は最低1名必要です。削除できません。'); return false; @elseif($hasPrimaryClients) alert('{{ $counselor->name }} は {{ $counselor->primary_clients_count }} 件のクライアントの主担当カウンセラーです。先に主担当を変更してから削除してください。'); return false; @elseif($hasRecords) alert('{{ $counselor->name }} は相談記録の担当者です。削除できません。'); return false; @else return confirm('「{{ $counselor->name }}」を削除しますか？\nこの操作は取り消せません。'); @endif">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
                                        </form>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
