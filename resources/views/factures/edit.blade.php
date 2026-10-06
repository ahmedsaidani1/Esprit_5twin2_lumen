@extends('layouts.app')
@section('title', 'Modifier facture')
@section('content')
<h2>Modifier la facture #{{ $facture->id }}</h2>
<form action="{{ route('factures.update', $facture) }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    @include('factures._form')
</form>
@endsection