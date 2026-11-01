@extends('layouts.app')
@section('title', 'Connexion')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Connexion</h2>
        <form action="{{ route('login.store') }}" method="POST" class="card card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus maxlength="255" autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="current-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" name="remember" value="1" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">Se souvenir de moi</label>
                </div>
                <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            </div>
            <button class="btn btn-success">Se connecter</button>
        </form>
        <p class="mt-3">Pas encore de compte ? <a href="{{ route('register') }}">S'inscrire</a></p>
    </div>
</div>
@endsection
