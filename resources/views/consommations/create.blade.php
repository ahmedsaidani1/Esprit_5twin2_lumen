@extends('layouts.app')
@section('title', 'Nouveau relevé')
@section('content')
<h2>Nouveau relevé de consommation</h2>
<form action="{{ route('consommations.store') }}" method="POST" class="card card-body">
    @csrf
    @include('consommations._form')
</form>
@endsection
