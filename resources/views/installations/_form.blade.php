@php $i = $installation ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nom</label>
        <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom', $i->nom ?? '') }}" required maxlength="255">
        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Propriétaire</label>
        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('user_id', $i->user_id ?? '') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Communauté</label>
        <select name="communaute_id" class="form-select @error('communaute_id') is-invalid @enderror">
            <option value="">-- Aucune --</option>
            @foreach($communautes as $c)
                <option value="{{ $c->id }}" @selected(old('communaute_id', $i->communaute_id ?? '') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
        @error('communaute_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6"></div>
    <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach(\App\Models\Installation::TYPES as $t)
                <option value="{{ $t }}" @selected(old('type', $i->type ?? 'solaire') == $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Puissance (kW)</label>
        <input type="number" step="0.01" name="puissance_kw" class="form-control @error('puissance_kw') is-invalid @enderror"
               value="{{ old('puissance_kw', $i->puissance_kw ?? '') }}" required min="0.01">
        @error('puissance_kw')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Localisation</label>
        <input type="text" name="localisation" class="form-control @error('localisation') is-invalid @enderror" value="{{ old('localisation', $i->localisation ?? '') }}" maxlength="255">
        @error('localisation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('installations.index') }}" class="btn btn-secondary">Annuler</a>
</div>
