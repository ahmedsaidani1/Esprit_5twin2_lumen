@extends('layouts.app')
@section('title', 'Nouvelle installation')
@section('content')
<h2>Nouvelle installation</h2>
<form action="{{ route('installations.store') }}" method="POST" class="card card-body">
    @csrf
    @include('installations._form')
</form>
@endsection
