@extends('layouts.app')

@section('title', '会員登録')

@section('content')
    @include('clients._form', [
        'client'      => null,
        'trainers'    => $trainers,
        'action'      => route('clients.store'),
        'method'      => 'POST',
        'submitLabel' => '登録',
        'cancelUrl'   => route('clients.index'),
        'pageTitle'   => '会員登録',
    ])
@endsection
