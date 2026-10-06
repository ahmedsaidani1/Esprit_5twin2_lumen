@extends('layouts.app')
@section('title', 'Nouvelle communauté')
@section('content')
<h2>Nouvelle communauté</h2>
<form action="{{ route('communautes.store') }}" method="POST" class="card card-body">
    @csrf
    @include('communautes._form')
</form>
@endsection
