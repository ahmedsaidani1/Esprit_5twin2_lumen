@extends('layouts.app')
@section('title', 'Modifier échange')
@section('content')
<h2>Modifier l'échange #{{ $echange->id }}</h2>
<form action="{{ route('echanges.update', $echange) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('echanges._form')
</form>
@endsection