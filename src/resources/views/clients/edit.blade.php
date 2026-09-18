@extends('layouts.app')

@section('title', '会員編集')

@section('content')
    @include('clients._form', [
        'client'      => $client,
        'trainers'    => $trainers,
        'action'      => route('clients.update', $client),
        'method'      => 'PUT',
        'submitLabel' => '更新',
        'cancelUrl'   => route('clients.show', $client),
        'pageTitle'   => '会員編集',
    ])
@endsection
