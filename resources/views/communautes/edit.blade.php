@extends('layouts.app')
@section('title', 'Modifier communauté')
@section('content')
<h2>Modifier la communauté #{{ $communaute->id }}</h2>
<form action="{{ route('communautes.update', $communaute) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('communautes._form')
</form>
@endsection
