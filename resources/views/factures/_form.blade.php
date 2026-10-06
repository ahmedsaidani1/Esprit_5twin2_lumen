@php $f = $facture ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Échange</label>
        <select name="echange_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            @foreach($echanges as $e)
                <option value="{{ $e->id }}" @selected(old('echange_id', $f->echange_id ?? '') == $e->id)>
                    #{{ $e->id }} : {{ $e->vendeur->name }} → {{ $e->acheteur->name }} ({{ $e->quantite_kwh }} kWh)
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Montant total (laisser vide = calcul automatique)</label>
        <input type="number" step="0.01" name="montant_total" class="form-control"
               value="{{ old('montant_total', $f->montant_total ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date d'émission</label>
        <input type="date" name="date_emission" class="form-control"
               value="{{ old('date_emission', isset($f) ? $f->date_emission->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date d'échéance</label>
        <input type="date" name="date_echeance" class="form-control"
               value="{{ old('date_echeance', isset($f) ? $f->date_echeance->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select" required>
            @foreach(\App\Models\Facture::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $f->statut ?? 'impayee') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('factures.index') }}" class="btn btn-secondary">Annuler</a>
</div>