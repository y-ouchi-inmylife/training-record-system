@extends('layouts.client')

@section('title', 'ダッシュボード - トレーニング記録閲覧')

@section('content')
<div class="container py-4">
    <h1 class="h4">{{ auth('client')->user()->full_name }} さん、ようこそ</h1>
</div>
@endsection
