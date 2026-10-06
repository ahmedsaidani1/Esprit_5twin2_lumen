@extends('layouts.app')
@section('title', 'Mon profil')
@section('content')
<h2>Mon profil</h2>
<form action="{{ route('profile.update') }}" method="POST" class="card card-body">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nom</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required maxlength="255">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Communauté</label>
            <select name="communaute_id" class="form-select @error('communaute_id') is-invalid @enderror">
                <option value="">-- Aucune --</option>
                @foreach($communautes as $c)
                    <option value="{{ $c->id }}" @selected(old('communaute_id', $user->communaute_id) == $c->id)>{{ $c->nom }}</option>
                @endforeach
            </select>
            @error('communaute_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Rôle</label>
            <input type="text" class="form-control" value="{{ $user->role }}" disabled>
        </div>
    </div>

    <hr>
    <h5>Changer le mot de passe (laisser vide pour le garder)</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Mot de passe actuel</label>
            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Nouveau mot de passe</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Confirmation</label>
            <input type="password" name="password_confirmation" class="form-control" minlength="8">
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-success">Enregistrer</button>
    </div>
</form>
@endsection
