@php $u = $user ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nom</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $u->name ?? '') }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $u->email ?? '') }}" required maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Rôle</label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach(\App\Models\User::ROLES as $r)
                <option value="{{ $r }}" @selected(old('role', $u->role ?? 'membre') == $r)>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">Communauté</label>
        <select name="communaute_id" class="form-select @error('communaute_id') is-invalid @enderror">
            <option value="">-- Aucune --</option>
            @foreach($communautes as $c)
                <option value="{{ $c->id }}" @selected(old('communaute_id', $u->communaute_id ?? '') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
        @error('communaute_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Mot de passe @isset($u)(laisser vide pour ne pas le changer)@endisset</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               minlength="8" @empty($u) required @endempty>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirmation</label>
        <input type="password" name="password_confirmation" class="form-control" minlength="8" @empty($u) required @endempty>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Annuler</a>
</div>
