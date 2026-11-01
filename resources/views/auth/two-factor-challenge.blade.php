@extends('layouts.app')
@section('title', 'Double authentification')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Double authentification</h2>
        <form action="{{ route('two-factor.login.store') }}" method="POST" class="card card-body mb-3">
            @csrf
            <p>Saisissez le code à 6 chiffres affiché par votre application d'authentification.</p>
            <div class="mb-3">
                <label class="form-label" for="code">Code</label>
                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-success">Valider</button>
        </form>

        <form action="{{ route('two-factor.login.store') }}" method="POST" class="card card-body">
            @csrf
            <p class="mb-2">Vous n'avez plus accès à l'application ? Utilisez un de vos codes de récupération.</p>
            <div class="mb-3">
                <label class="form-label" for="recovery_code">Code de récupération</label>
                <input type="text" id="recovery_code" name="recovery_code"
                       class="form-control @error('recovery_code') is-invalid @enderror" maxlength="50" autocomplete="off">
                @error('recovery_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-outline-success">Utiliser un code de récupération</button>
        </form>
    </div>
</div>
@endsection
