<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SolarShare - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-success mb-4">
    <div class="container">
        @auth
            <a class="navbar-brand" href="{{ route('dashboard') }}">☀ SolarShare</a>
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">Accueil</a>
                <a class="nav-link" href="{{ route('communautes.index') }}">Communautés</a>
                <a class="nav-link" href="{{ route('echanges.index') }}">Échanges</a>
                <a class="nav-link" href="{{ route('factures.index') }}">Factures</a>
                <a class="nav-link" href="{{ route('installations.index') }}">Installations</a>
                <a class="nav-link" href="{{ route('productions.index') }}">Productions</a>
                <a class="nav-link" href="{{ route('offres.index') }}">Offres</a>
                @can('viewAny', \App\Models\User::class)
                    <a class="nav-link" href="{{ route('users.index') }}">Utilisateurs</a>
                @endcan
            </div>
            <div class="navbar-nav align-items-center">
                <a class="nav-link" href="{{ route('profile.edit') }}">
                    {{ auth()->user()->name }}
                    <span class="badge bg-light text-success">{{ auth()->user()->role }}</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-light ms-2">Déconnexion</button>
                </form>
            </div>
        @else
            <a class="navbar-brand" href="{{ route('login') }}">☀ SolarShare</a>
            <div class="navbar-nav">
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
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
