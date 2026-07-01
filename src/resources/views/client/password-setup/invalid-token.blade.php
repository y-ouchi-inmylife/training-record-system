@extends('layouts.client-public')

@section('title', $title . ' - トレーニング記録閲覧')

@section('content')
<div class="py-5">
    <div class="text-center">
        <h2 class="mb-4" style="font-size: 2rem;">{{ $title }}</h2>
        <p class="mb-5" style="font-size: 1.25rem;">{{ $message }}</p>
    </div>
</div>
@endsection
