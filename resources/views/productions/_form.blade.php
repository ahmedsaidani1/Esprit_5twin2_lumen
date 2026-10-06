@php $p = $production ?? null; @endphp
<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label">Installation</label>
        <select name="installation_id" class="form-select @error('installation_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($installations as $i)
                <option value="{{ $i->id }}" @selected(old('installation_id', $p->installation_id ?? request('installation_id')) == $i->id)>{{ $i->nom }}</option>
            @endforeach
        </select>
        @error('installation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
               value="{{ old('date', isset($p) ? $p->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Énergie produite (kWh)</label>
        <input type="number" step="0.01" name="energie_produite_kwh" class="form-control @error('energie_produite_kwh') is-invalid @enderror"
               value="{{ old('energie_produite_kwh', $p->energie_produite_kwh ?? '') }}" required min="0">
        @error('energie_produite_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('productions.index') }}" class="btn btn-secondary">Annuler</a>
</div>
