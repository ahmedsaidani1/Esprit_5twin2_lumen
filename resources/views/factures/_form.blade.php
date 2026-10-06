@php $f = $facture ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Échange</label>
        <select name="echange_id" class="form-select @error('echange_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($echanges as $e)
                <option value="{{ $e->id }}" @selected(old('echange_id', $f->echange_id ?? request('echange_id')) == $e->id)>
                    #{{ $e->id }} : {{ $e->vendeur->name }} → {{ $e->acheteur->name }} ({{ $e->quantite_kwh }} kWh)
                </option>
            @endforeach
        </select>
        @error('echange_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Montant total (laisser vide = calcul automatique)</label>
        <input type="number" step="0.01" name="montant_total" class="form-control @error('montant_total') is-invalid @enderror"
               value="{{ old('montant_total', $f->montant_total ?? '') }}" min="0">
        @error('montant_total')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Date d'émission</label>
        <input type="date" name="date_emission" class="form-control @error('date_emission') is-invalid @enderror"
               value="{{ old('date_emission', isset($f) ? $f->date_emission->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('date_emission')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Date d'échéance</label>
        <input type="date" name="date_echeance" class="form-control @error('date_echeance') is-invalid @enderror"
               value="{{ old('date_echeance', isset($f) ? $f->date_echeance->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" required>
        @error('date_echeance')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select @error('statut') is-invalid @enderror" required>
            @foreach(\App\Models\Facture::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $f->statut ?? 'impayee') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('factures.index') }}" class="btn btn-secondary">Annuler</a>
</div>