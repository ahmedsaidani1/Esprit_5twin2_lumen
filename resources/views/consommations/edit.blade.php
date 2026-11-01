@extends('layouts.app')
@section('title', 'Modifier relevé')
@section('content')
<h2>Modifier le relevé du {{ $consommation->date->format('d/m/Y') }}</h2>
<form action="{{ route('consommations.update', $consommation) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('consommations._form')
</form>
@endsection
