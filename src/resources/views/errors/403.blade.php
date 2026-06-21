@extends('layouts.app')

@section('title', 'アクセス拒否')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">アクセス拒否</div>
                <div class="card-body">
                    <h4>403 Forbidden</h4>
                    <p>{{ $exception->getMessage() ?: 'このページへのアクセスは許可されていません。' }}</p>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="{{ url('/login') }}" class="btn btn-primary">ログイン画面に戻る</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
