@extends('layouts.app')
@section('title', 'Nouveau compteur')
@section('content')
<h2>Nouveau compteur</h2>
<form action="{{ route('compteurs.store') }}" method="POST" class="card card-body">
    @csrf
    @include('compteurs._form')
</form>
@endsection
