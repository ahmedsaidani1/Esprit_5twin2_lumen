@extends('layouts.app')
@section('title', 'Modifier compteur')
@section('content')
<h2>Modifier le compteur {{ $compteur->reference }}</h2>
<form action="{{ route('compteurs.update', $compteur) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('compteurs._form')
</form>
@endsection
