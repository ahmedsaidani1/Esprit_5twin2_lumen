@extends('layouts.app')
@section('title', 'Modifier offre')
@section('content')
<h2>Modifier l'offre #{{ $offre->id }}</h2>
<form action="{{ route('offres.update', $offre) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('offres._form')
</form>
@endsection
