@extends('layouts.app')
@section('title', 'Nouvel utilisateur')
@section('content')
<h2>Nouvel utilisateur</h2>
<form action="{{ route('users.store') }}" method="POST" class="card card-body">
    @csrf
    @include('users._form')
</form>
@endsection
