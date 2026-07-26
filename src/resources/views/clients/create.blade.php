@extends('layouts.app')

@section('title', 'クライアント登録')

@section('content')
    @include('clients._form', [
        'client'      => null,
        'trainers'    => $trainers,
        'action'      => route('clients.store'),
        'method'      => 'POST',
        'submitLabel' => '登録',
        'cancelUrl'   => route('clients.index'),
        'pageTitle'   => 'クライアント登録',
    ])
@endsection
