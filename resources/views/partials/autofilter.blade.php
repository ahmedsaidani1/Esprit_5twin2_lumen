{{-- Filtres dynamiques : la liste se met à jour sans recharger la page.
     Sans JavaScript, le formulaire GET (Entrée / bouton Filtrer) continue de fonctionner. --}}
<script>
(() => {
    const form = document.querySelector('form[data-autofilter]');
    const liste = document.getElementById('liste');
    if (!form || !liste) return;

    let timer, controller;

    const refresh = async () => {
        const params = new URLSearchParams();
        new FormData(form).forEach((value, key) => { if (value !== '') params.append(key, value); });

        const url = new URL(form.action, location.href);
        url.search = params.toString();

        controller?.abort();              // ignore une réponse devenue obsolète
        controller = new AbortController();

        try {
            const res = await fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return form.submit();
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const fresh = doc.getElementById('liste');
            if (!fresh) return form.submit();
            liste.innerHTML = fresh.innerHTML;
            history.replaceState(null, '', url);
        } catch (e) {
            if (e.name !== 'AbortError') form.submit();
        }
    };

    form.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(timer); refresh(); });

    // listes déroulantes et dates : tout de suite
    form.addEventListener('change', (e) => {
        if (!e.target.matches('input[type=text]')) refresh();
    });

    // champs de recherche : après une courte pause de frappe
    form.addEventListener('input', (e) => {
        if (e.target.matches('input[type=text]')) {
            clearTimeout(timer);
            timer = setTimeout(refresh, 300);
        }
    });
})();
</script>
