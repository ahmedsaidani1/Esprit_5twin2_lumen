@php $e = $echange ?? null; @endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Offre (optionnel : remplit vendeur, communauté, quantité et prix)</label>
        <select name="offre_id" id="offre_id" class="form-select @error('offre_id') is-invalid @enderror">
            <option value="">-- Aucune offre --</option>
            @foreach($offres as $o)
                <option value="{{ $o->id }}"
                        data-vendeur="{{ $o->installation->user_id }}"
                        data-communaute="{{ $o->installation->communaute_id }}"
                        data-quantite="{{ $o->quantite_kwh }}"
                        data-prix="{{ $o->prix_kwh }}"
                        @selected(old('offre_id', $e->offre_id ?? request('offre_id')) == $o->id)>
                    Offre #{{ $o->id }} : {{ $o->installation->nom }} ({{ $o->quantite_kwh }} kWh à {{ $o->prix_kwh }})
                </option>
            @endforeach
        </select>
        @error('offre_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Vendeur</label>
        <select name="vendeur_id" class="form-select @error('vendeur_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('vendeur_id', $e->vendeur_id ?? '') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('vendeur_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Acheteur</label>
        <select name="acheteur_id" class="form-select @error('acheteur_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('acheteur_id', $e->acheteur_id ?? auth()->id()) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('acheteur_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Communauté</label>
        <select name="communaute_id" class="form-select @error('communaute_id') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            @foreach($communautes as $c)
                <option value="{{ $c->id }}" @selected(old('communaute_id', $e->communaute_id ?? '') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
        @error('communaute_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Quantité (kWh)</label>
        <input type="number" step="0.01" name="quantite_kwh" class="form-control @error('quantite_kwh') is-invalid @enderror"
               value="{{ old('quantite_kwh', $e->quantite_kwh ?? '') }}" required min="0.01">
        @error('quantite_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Prix unitaire</label>
        <input type="number" step="0.0001" name="prix_unitaire" class="form-control @error('prix_unitaire') is-invalid @enderror"
               value="{{ old('prix_unitaire', $e->prix_unitaire ?? '') }}" required min="0">
        @error('prix_unitaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Date de l'échange</label>
        <input type="datetime-local" name="date_echange" class="form-control @error('date_echange') is-invalid @enderror"
               value="{{ old('date_echange', isset($e) ? $e->date_echange->format('Y-m-d\TH:i') : '') }}" required>
        @error('date_echange')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select @error('statut') is-invalid @enderror" required>
            @foreach(\App\Models\Echange::STATUTS as $s)
                <option value="{{ $s }}" @selected(old('statut', $e->statut ?? 'propose') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-success">Enregistrer</button>
    <a href="{{ route('echanges.index') }}" class="btn btn-secondary">Annuler</a>
</div>
<script>
    const offreSelect = document.getElementById('offre_id');
    // force = true : l'utilisateur vient de choisir une offre, on remplace les valeurs.
    // force = false : au chargement (lien "Acheter" depuis une offre), on ne remplit que les champs vides,
    // pour ne pas écraser une saisie conservée après une erreur ni les valeurs d'un échange existant.
    function remplirDepuisOffre(force) {
        const opt = offreSelect.selectedOptions[0];
        if (!opt || !opt.value) return;
        const set = (name, v) => {
            const el = document.querySelector(`[name=${name}]`);
            if (el && v && (force || !el.value)) el.value = v;
        };
        set('vendeur_id', opt.dataset.vendeur);
        set('communaute_id', opt.dataset.communaute);
        set('quantite_kwh', opt.dataset.quantite);
        set('prix_unitaire', opt.dataset.prix);
    }
    offreSelect.addEventListener('change', () => remplirDepuisOffre(true));
    remplirDepuisOffre(false);
</script>