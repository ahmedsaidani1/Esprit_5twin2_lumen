@extends('layouts.app')
@section('title', 'Mot de passe oublié')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Mot de passe oublié</h2>
        <p class="text-muted">Indiquez votre e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.
            Le lien expire au bout de {{ config('auth.passwords.users.expire') }} minutes.</p>
        <form action="{{ route('password.email') }}" method="POST" class="card card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus maxlength="255" autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-success">Envoyer le lien</button>
        </form>
        <p class="mt-3"><a href="{{ route('login') }}">Retour à la connexion</a></p>
    </div>
</div>
@endsection
