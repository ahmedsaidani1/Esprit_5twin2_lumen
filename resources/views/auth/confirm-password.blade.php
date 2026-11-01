@extends('layouts.app')
@section('title', 'Confirmer le mot de passe')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Confirmez votre mot de passe</h2>
        <p class="text-muted">Cette action est sensible : confirmez votre mot de passe pour continuer.</p>
        <form action="{{ route('password.confirm.store') }}" method="POST" class="card card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required autofocus autocomplete="current-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-success">Confirmer</button>
        </form>
    </div>
</div>
@endsection
