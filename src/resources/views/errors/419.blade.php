@extends('layouts.error')

@section('title', 'セッションの有効期限が切れました')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">セッションの有効期限が切れました</div>
                <div class="card-body">
                    <h4>419 Page Expired</h4>
                    <p>しばらく操作がなかったため、セッションの有効期限が切れました。再度ログインしてください。</p>
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
