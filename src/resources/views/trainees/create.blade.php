@extends('layouts.app')

@section('title', 'トレーニー登録')

@section('content')
    @include('trainees._form', [
        'trainee'     => null,
        'client'      => $client,
        'action'      => route('trainees.store', $client),
        'method'      => 'POST',
        'submitLabel' => '登録',
        'cancelUrl'   => route('clients.show', $client),
        'pageTitle'   => 'トレーニー登録',
    ])
@endsection
