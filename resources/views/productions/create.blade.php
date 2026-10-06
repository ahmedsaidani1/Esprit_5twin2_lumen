@extends('layouts.app')
@section('title', 'Nouvelle production')
@section('content')
<h2>Nouvelle production</h2>
<form action="{{ route('productions.store') }}" method="POST" class="card card-body">
    @csrf
    @include('productions._form')
</form>
@endsection
