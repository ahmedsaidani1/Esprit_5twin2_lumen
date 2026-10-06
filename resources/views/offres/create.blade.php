@extends('layouts.app')
@section('title', 'Nouvelle offre')
@section('content')
<h2>Nouvelle offre</h2>
<form action="{{ route('offres.store') }}" method="POST" class="card card-body">
    @csrf
    @include('offres._form')
</form>
@endsection
