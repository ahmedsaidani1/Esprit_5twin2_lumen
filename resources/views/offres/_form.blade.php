@php $o = $offre ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Installation</label>
        <select name="installation_id" class="form-select @error('installation_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($installations as $i)
                <option value="{{ $i->id }}" @selected(old('installation_id', $o->installation_id ?? request('installation_id')) == $i->id)>{{ $i->nom }}</option>
            @endforeach
        </select>
        @error('installation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Quantité (kWh)</label>
        <input type="number" step="0.01" name="quantite_kwh" class="form-control @error('quantite_kwh') is-invalid @enderror"
               value="{{ old('quantite_kwh', $o->quantite_kwh ?? '') }}" required min="0.01">
        @error('quantite_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Prix (par kWh)</label>
        <input type="number" step="0.0001" name="prix_kwh" class="form-control @error('prix_kwh') is-invalid @enderror"
               value="{{ old('prix_kwh', $o->prix_kwh ?? '') }}" required min="0">
        @error('prix_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Date de début</label>
        <input type="date" name="date_debut" class="form-control @error('date_debut') is-invalid @enderror"
               value="{{ old('date_debut', isset($o) ? $o->date_debut->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('date_debut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Date de fin</label>
        <input type="date" name="date_fin" class="form-control @error('date_fin') is-invalid @enderror"
               value="{{ old('date_fin', isset($o) ? $o->date_fin->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" required>
        @error('date_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select @error('statut') is-invalid @enderror" required>
            @foreach(\App\Models\Offre::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $o->statut ?? 'disponible') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('offres.index') }}" class="btn btn-secondary">Annuler</a>
</div>
