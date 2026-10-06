@extends('layouts.app')
@section('title', 'Modifier utilisateur')
@section('content')
<h2>Modifier l'utilisateur #{{ $user->id }}</h2>
<form action="{{ route('users.update', $user) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('users._form')
</form>
@endsection
