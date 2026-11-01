@php $r = $consommation ?? null; @endphp
<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label" for="compteur_id">Compteur</label>
        <select id="compteur_id" name="compteur_id" class="form-select @error('compteur_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($compteurs as $c)
                <option value="{{ $c->id }}" @selected(old('compteur_id', $r->compteur_id ?? request('compteur_id')) == $c->id)>
                    {{ $c->reference }}@if($c->statut !== 'actif') ({{ str_replace('_', ' ', $c->statut) }})@endif
                </option>
            @endforeach
        </select>
        <div class="form-text">Seuls les compteurs actifs acceptent de nouveaux relevés.</div>
        @error('compteur_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="date">Date</label>
        <input type="date" id="date" name="date" max="{{ now()->format('Y-m-d') }}"
               class="form-control @error('date') is-invalid @enderror"
               value="{{ old('date', isset($r) ? $r->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="energie_consommee_kwh">Énergie consommée (kWh)</label>
        <input type="number" step="0.01" min="0" max="100000" id="energie_consommee_kwh" name="energie_consommee_kwh"
               class="form-control @error('energie_consommee_kwh') is-invalid @enderror"
               value="{{ old('energie_consommee_kwh', $r->energie_consommee_kwh ?? '') }}" required>
        @error('energie_consommee_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('consommations.index') }}" class="btn btn-secondary">Annuler</a>
</div>
