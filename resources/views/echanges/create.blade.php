@extends('layouts.app')
@section('title', 'Nouvel échange')
@section('content')
<h2>Nouvel échange</h2>
<form action="{{ route('echanges.store') }}" method="POST" class="card card-body">
    @csrf
    @include('echanges._form')
</form>
@endsection