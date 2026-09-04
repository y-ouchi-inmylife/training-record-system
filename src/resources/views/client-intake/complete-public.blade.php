@extends('layouts.client-intake')

@php
    // 他の client 側画面と同じ config キー(§9-1・§9-2)
    $companyName = config('app.client_portal_company');
@endphp

@section('content')
{{-- 完了画面(設計書 §4-6)。cobalt の checkmark 円 + 見出し + 説明文。
     「このウィンドウを閉じてください」は突き放し文言のため §6 に従って
     「トレーナーからご連絡があるまで、少々お待ちください」に書き換え --}}
<div class="c-intake-complete">
    <div class="c-intake-complete-check" aria-hidden="true">✓</div>
    <h1 class="c-intake-complete-title">送信が完了しました</h1>
    <p class="c-intake-complete-body">
        トレーナーからご連絡があるまで、少々お待ちください。
    </p>

    @if($companyName)
        <p class="c-intake-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
