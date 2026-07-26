@extends('layouts.app')

@section('title', 'クライアント編集')

@section('content')
    @include('clients._form', [
        'client'      => $client,
        'trainers'    => $trainers,
        'action'      => route('clients.update', $client),
        'method'      => 'PUT',
        'submitLabel' => '更新',
        'cancelUrl'   => route('clients.show', $client),
        'pageTitle'   => 'クライアント編集',
    ])
@endsection
