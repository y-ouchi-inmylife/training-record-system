@extends('layouts.app')

@section('title', 'トレーニー編集')

@section('content')
    @include('trainees._form', [
        'trainee'     => $trainee,
        'client'      => $trainee->client,
        'action'      => route('trainees.update', $trainee),
        'method'      => 'PUT',
        'submitLabel' => '更新',
        'cancelUrl'   => route('trainees.show', $trainee),
        'pageTitle'   => 'トレーニー編集',
    ])
@endsection
