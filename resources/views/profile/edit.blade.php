@extends('layouts.app')
@section('title', 'Mon profil')
@section('content')
@php
    // Les actions 2FA exigent une confirmation récente du mot de passe (Fortify, 3 h par défaut).
    $motDePasseConfirme = time() - (int) session('auth.password_confirmed_at', 0) < (int) config('auth.password_timeout', 10800);
@endphp
<h2 class="mb-3">Mon profil</h2>

{{-- 1. Informations et photo --}}
<div class="card card-body mb-4">
    <h5>Informations</h5>
    <form action="{{ route('user-profile-information.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-3 text-center">
                @if($user->photoUrl())
                    <img src="{{ $user->photoUrl() }}" alt="Photo de profil" class="rounded-circle mb-2" width="120" height="120" style="object-fit:cover">
                @else
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2"
                         style="width:120px;height:120px;font-size:2.5rem">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                @endif
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                       class="form-control form-control-sm @error('photo', 'updateProfileInformation') is-invalid @enderror">
                <div class="form-text">JPG, PNG ou WebP, 2 Mo max.</div>
                @error('photo', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($user->photo_path)
                    <div class="form-check text-start mt-1">
                        <input type="checkbox" name="supprimer_photo" value="1" id="supprimer_photo" class="form-check-input">
                        <label for="supprimer_photo" class="form-check-label">Supprimer la photo</label>
                    </div>
                @endif
            </div>
            <div class="col-md-9">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nom</label>
                        <input type="text" id="name" name="name" class="form-control @error('name', 'updateProfileInformation') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required maxlength="255">
                        @error('name', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control @error('email', 'updateProfileInformation') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required maxlength="255">
                        <div class="form-text">Changer d'e-mail demande une nouvelle vérification.</div>
                        @error('email', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="communaute_id">Communauté</label>
                        <select id="communaute_id" name="communaute_id" class="form-select @error('communaute_id', 'updateProfileInformation') is-invalid @enderror">
                            <option value="">-- Aucune --</option>
                            @foreach($communautes as $c)
                                <option value="{{ $c->id }}" @selected(old('communaute_id', $user->communaute_id) == $c->id)>{{ $c->nom }}</option>
                            @endforeach
                        </select>
                        @error('communaute_id', 'updateProfileInformation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rôle</label>
                        <input type="text" class="form-control" value="{{ $user->role }}" disabled>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-success mt-3">Enregistrer</button>
    </form>
</div>

{{-- 2. Mot de passe --}}
<div class="card card-body mb-4">
    <h5>Mot de passe</h5>
    <form action="{{ route('user-password.update') }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                       class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="password_confirmation">Confirmation</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                       autocomplete="new-password" class="form-control">
            </div>
        </div>
        <button class="btn btn-success mt-3">Changer le mot de passe</button>
    </form>
</div>

{{-- 3. Double authentification (TOTP) --}}
<div class="card card-body mb-4" id="2fa">
    <h5>Double authentification (2FA)</h5>

    @if(!$motDePasseConfirme)
        <p class="mb-2">
            Statut : <strong>{{ $user->hasTwoFactorEnabled() ? 'activée' : 'désactivée' }}</strong>.
            Pour gérer la double authentification, confirmez d'abord votre mot de passe.
        </p>
        <a href="{{ route('profile.confirm') }}" class="btn btn-outline-success align-self-start">Confirmer mon mot de passe</a>

    @elseif(!$user->two_factor_secret)
        <p>Protégez votre compte : à chaque connexion, un code à 6 chiffres sera demandé
            (Google Authenticator, Microsoft Authenticator, Authy…).</p>
        <form action="{{ route('two-factor.enable') }}" method="POST">
            @csrf
            <button class="btn btn-success">Activer la double authentification</button>
        </form>

    @elseif(!$user->two_factor_confirmed_at)
        <p>1. Scannez ce QR code avec votre application d'authentification :</p>
        <div class="mb-2">{!! $user->twoFactorQrCodeSvg() !!}</div>
        <p class="small text-muted">Ou saisissez la clé : <code>{{ decrypt($user->two_factor_secret) }}</code></p>
        <p>2. Saisissez le code affiché pour terminer l'activation :</p>
        <form action="{{ route('two-factor.confirm') }}" method="POST" class="row g-2 mb-3">
            @csrf
            <div class="col-auto">
                <input type="text" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                       class="form-control @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror" placeholder="123456">
                @error('code', 'confirmTwoFactorAuthentication')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button class="btn btn-success">Confirmer</button></div>
        </form>
        <form action="{{ route('two-factor.disable') }}" method="POST">
            @csrf @method('DELETE')
            <button class="btn btn-outline-secondary btn-sm">Annuler</button>
        </form>

    @else
        <p class="text-success mb-2"><strong>La double authentification est activée.</strong></p>
        <p class="mb-1">Codes de récupération (à conserver en lieu sûr, chacun n'est utilisable qu'une fois) :</p>
        <pre class="bg-light border rounded p-2">@foreach($user->recoveryCodes() as $code){{ $code }}
@endforeach</pre>
        <div class="d-flex gap-2">
            <form action="{{ route('two-factor.regenerate-recovery-codes') }}" method="POST">
                @csrf
                <button class="btn btn-outline-success">Régénérer les codes</button>
            </form>
            <form action="{{ route('two-factor.disable') }}" method="POST">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">Désactiver la double authentification</button>
            </form>
        </div>
    @endif
</div>

{{-- 4. Suppression du compte --}}
<div class="card card-body mb-4 border-danger">
    <h5 class="text-danger">Supprimer mon compte</h5>
    @if($user->aDesEchanges())
        <p class="mb-0">Votre compte ne peut pas être supprimé : vous avez des échanges, qui doivent être conservés
            (comptabilité). Contactez un administrateur.</p>
    @else
        <p>Cette action est définitive : vos installations, productions, offres, compteurs et relevés seront supprimés.</p>
        <form action="{{ route('profile.destroy') }}" method="POST" class="row g-2">
            @csrf @method('DELETE')
            <div class="col-md-4">
                <input type="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe"
                       class="form-control @error('password', 'deleteAccount') is-invalid @enderror">
                @error('password', 'deleteAccount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button class="btn btn-danger">Supprimer définitivement</button></div>
        </form>
    @endif
</div>
@endsection
