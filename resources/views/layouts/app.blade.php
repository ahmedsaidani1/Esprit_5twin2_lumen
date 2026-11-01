<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SolarShare - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">
        @auth
            @php $moi = auth()->user(); $nonLues = $moi->unreadNotifications()->count(); @endphp
            <a class="navbar-brand" href="{{ route('dashboard') }}">☀ SolarShare</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Ouvrir le menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Énergie</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('installations.index') }}">Installations</a></li>
                            <li><a class="dropdown-item" href="{{ route('productions.index') }}">Productions</a></li>
                            <li><a class="dropdown-item" href="{{ route('offres.index') }}">Offres</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Marché</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('echanges.index') }}">Échanges</a></li>
                            <li><a class="dropdown-item" href="{{ route('factures.index') }}">Factures</a></li>
                            <li><a class="dropdown-item" href="{{ route('communautes.index') }}">Communautés</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Consommation</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('compteurs.index') }}">Compteurs</a></li>
                            <li><a class="dropdown-item" href="{{ route('consommations.index') }}">Relevés</a></li>
                            @can('utiliser-ia')
                                <li><a class="dropdown-item" href="{{ route('analyse.consommation') }}">Mon analyse</a></li>
                            @endcan
                        </ul>
                    </li>
                    @can('utiliser-ia')
                        <li class="nav-item"><a class="nav-link" href="{{ route('ia.index') }}">Laboratoire IA</a></li>
                    @endcan
                    @can('viewAny', \App\Models\User::class)
                        <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">Utilisateurs</a></li>
                    @endcan
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link position-relative me-2" href="{{ route('notifications.index') }}"
                           aria-label="Notifications{{ $nonLues ? ' : ' . $nonLues . ' non lue(s)' : '' }}">
                            🔔@if($nonLues)<span class="badge rounded-pill bg-danger">{{ $nonLues }}</span>@endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                            @if($moi->photoUrl())
                                <img src="{{ $moi->photoUrl() }}" alt="" width="28" height="28" class="rounded-circle" style="object-fit:cover">
                            @endif
                            {{ $moi->name }}
                            <span class="badge bg-light text-success">{{ $moi->role }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-light ms-lg-2">Déconnexion</button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <a class="navbar-brand" href="{{ route('login') }}">☀ SolarShare</a>
            <div class="navbar-nav flex-row gap-3">
                <a class="nav-link" href="{{ route('login') }}">Connexion</a>
                <a class="nav-link" href="{{ route('register') }}">Inscription</a>
            </div>
        @endauth
    </div>
</nav>
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('status'))
        @php
            // Statuts renvoyés par Fortify (les autres sont déjà des phrases traduites)
            $statuts = [
                'profile-information-updated' => 'Profil mis à jour.',
                'password-updated' => 'Mot de passe modifié.',
                'two-factor-authentication-enabled' => 'Double authentification en cours d\'activation : scannez le QR code puis saisissez un code.',
                'two-factor-authentication-confirmed' => 'Double authentification activée.',
                'two-factor-authentication-disabled' => 'Double authentification désactivée.',
                'recovery-codes-generated' => 'Nouveaux codes de récupération générés.',
                'verification-link-sent' => 'Un nouveau lien de vérification vous a été envoyé.',
            ];
        @endphp
        <div class="alert alert-info">{{ $statuts[session('status')] ?? session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
