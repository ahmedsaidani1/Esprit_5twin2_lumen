@php $c = $communaute ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nom</label>
        <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom', $c->nom ?? '') }}" required maxlength="255">
        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Ville</label>
        <input type="text" name="ville" class="form-control @error('ville') is-invalid @enderror" value="{{ old('ville', $c->ville ?? '') }}" maxlength="255">
        @error('ville')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('communautes.index') }}" class="btn btn-secondary">Annuler</a>
</div>
