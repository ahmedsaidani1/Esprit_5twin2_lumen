@extends('layouts.app')
@section('title', 'Nouvelle facture')
@section('content')
<h2>Nouvelle facture</h2>
<form action="{{ route('factures.store') }}" method="POST" class="card card-body">
    @csrf
    @include('factures._form')
</form>
@endsection