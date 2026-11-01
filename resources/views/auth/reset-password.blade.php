@extends('layouts.app')
@section('title', 'Nouveau mot de passe')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Choisir un nouveau mot de passe</h2>
        <form action="{{ route('password.update') }}" method="POST" class="card card-body">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $request->email) }}" required maxlength="255" autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required minlength="8" autofocus autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirmation</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <button class="btn btn-success">Enregistrer le mot de passe</button>
        </form>
    </div>
</div>
@endsection
