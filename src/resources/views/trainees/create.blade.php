@extends('layouts.app')

@section('title', 'トレーニーの新規登録')

@section('content')
    @include('trainees._form', [
        'trainee'     => null,
        'client'      => $client,
        'action'      => route('trainees.store', $client),
        'method'      => 'POST',
        'submitLabel' => '登録',
        'cancelUrl'   => route('clients.show', $client),
        'pageTitle'   => 'トレーニーの新規登録',
    ])
@endsection
