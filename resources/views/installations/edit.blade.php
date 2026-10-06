@extends('layouts.app')
@section('title', 'Modifier installation')
@section('content')
<h2>Modifier l'installation #{{ $installation->id }}</h2>
<form action="{{ route('installations.update', $installation) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('installations._form')
</form>
@endsection
