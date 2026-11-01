@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Notifications</h2>
    @if(auth()->user()->unreadNotifications()->exists())
        <form action="{{ route('notifications.tout-lire') }}" method="POST">
            @csrf
            <button class="btn btn-outline-success">Tout marquer comme lu</button>
        </form>
    @endif
</div>
<div class="list-group mb-3">
    @forelse($notifications as $n)
        <a href="{{ route('notifications.lire', $n->id) }}"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start @if(!$n->read_at) fw-semibold @endif">
            <span>
                @if(!$n->read_at)<span class="badge bg-success me-1">nouveau</span>@endif
                {{ $n->data['message'] ?? '' }}
            </span>
            <small class="text-muted text-nowrap ms-3">{{ $n->created_at->diffForHumans() }}</small>
        </a>
    @empty
        <div class="list-group-item text-center">Aucune notification.</div>
    @endforelse
</div>
{{ $notifications->links('pagination::bootstrap-5') }}
@endsection
