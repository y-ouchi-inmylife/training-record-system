{{-- クライアント認証中は layouts.client、それ以外（トレーナー・未認証）は layouts.app に載せる。
     layouts.app はナビで Auth::user()->isSystemAdmin() 等を呼ぶため、クライアント guard の
     ユーザーが 403 に到達すると BadMethodCallException で 500 化する。ガードで分岐して防ぐ。 --}}
@extends(auth('client')->check() ? 'layouts.client' : 'layouts.app')

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
