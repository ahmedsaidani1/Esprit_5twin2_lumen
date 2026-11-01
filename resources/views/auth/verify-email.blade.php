@extends('layouts.app')
@section('title', 'Vérifiez votre e-mail')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-3">Vérifiez votre adresse e-mail</h2>
        <div class="card card-body">
            <p>Avant de continuer, cliquez sur le lien que nous avons envoyé à
                <strong>{{ auth()->user()->email }}</strong>.
                Le lien expire au bout de {{ config('auth.verification.expire', 60) }} minutes.</p>
            <p class="text-muted mb-3">Vous ne l'avez pas reçu ? Vérifiez vos courriers indésirables ou demandez-en un nouveau.</p>
            <div class="d-flex gap-2">
                <form action="{{ route('verification.send') }}" method="POST">
                    @csrf
                    <button class="btn btn-success">Renvoyer le lien</button>
                </form>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-secondary">Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
