@extends('layouts.app')
@section('title', 'Modifier production')
@section('content')
<h2>Modifier la production #{{ $production->id }}</h2>
<form action="{{ route('productions.update', $production) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('productions._form')
</form>
@endsection
