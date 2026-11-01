@php $c = $compteur ?? null; @endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="reference">Référence</label>
        <input type="text" id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror"
               value="{{ old('reference', $c->reference ?? '') }}" required maxlength="50" placeholder="CPT-0001">
        @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="user_id">Propriétaire</label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('user_id', $c->user_id ?? auth()->id()) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="installation_id">Installation liée (optionnel)</label>
        <select id="installation_id" name="installation_id" class="form-select @error('installation_id') is-invalid @enderror">
            <option value="">-- Aucune --</option>
            @foreach($installations as $i)
                <option value="{{ $i->id }}" @selected(old('installation_id', $c->installation_id ?? '') == $i->id)>{{ $i->nom }}</option>
            @endforeach
        </select>
        @error('installation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="date_installation">Date d'installation</label>
        <input type="date" id="date_installation" name="date_installation" max="{{ now()->format('Y-m-d') }}"
               class="form-control @error('date_installation') is-invalid @enderror"
               value="{{ old('date_installation', isset($c) ? $c->date_installation->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('date_installation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="statut">Statut</label>
        <select id="statut" name="statut" class="form-select @error('statut') is-invalid @enderror" required>
            @foreach(\App\Models\Compteur::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $c->statut ?? 'actif') == $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('compteurs.index') }}" class="btn btn-secondary">Annuler</a>
</div>
