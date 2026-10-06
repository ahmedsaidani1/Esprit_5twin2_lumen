@extends('layouts.app')
@section('title', 'Inscription')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-3">Créer un compte</h2>
        <form action="{{ route('register') }}" method="POST" class="card card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nom</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required maxlength="255" autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required maxlength="255">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Communauté (optionnel)</label>
                <select name="communaute_id" class="form-select @error('communaute_id') is-invalid @enderror">
                    <option value="">-- Aucune --</option>
                    @foreach($communautes as $c)
                        <option value="{{ $c->id }}" @selected(old('communaute_id') == $c->id)>{{ $c->nom }}</option>
                    @endforeach
                </select>
                @error('communaute_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Mot de passe (8 caractères minimum)</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           required minlength="8">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmation</label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                </div>
            </div>
            <button class="btn btn-success">Créer mon compte</button>
        </form>
        <p class="mt-3">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    </div>
</div>
@endsection
