@php $e = $echange ?? null; @endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Vendeur</label>
        <select name="vendeur_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('vendeur_id', $e->vendeur_id ?? '') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Acheteur</label>
        <select name="acheteur_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('acheteur_id', $e->acheteur_id ?? '') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Communauté</label>
        <select name="communaute_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            @foreach($communautes as $c)
                <option value="{{ $c->id }}" @selected(old('communaute_id', $e->communaute_id ?? '') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Quantité (kWh)</label>
        <input type="number" step="0.01" name="quantite_kwh" class="form-control"
               value="{{ old('quantite_kwh', $e->quantite_kwh ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Prix unitaire</label>
        <input type="number" step="0.0001" name="prix_unitaire" class="form-control"
               value="{{ old('prix_unitaire', $e->prix_unitaire ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Date de l'échange</label>
        <input type="datetime-local" name="date_echange" class="form-control"
               value="{{ old('date_echange', isset($e) ? $e->date_echange->format('Y-m-d\TH:i') : '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select" required>
            @foreach(\App\Models\Echange::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $e->statut ?? 'propose') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('echanges.index') }}" class="btn btn-secondary">Annuler</a>
</div>