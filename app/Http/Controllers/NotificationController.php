<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Membre A — fonctionnalité avancée : centre de notifications de l'utilisateur. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(15),
        ]);
    }

    /** Ouvre une notification : elle est marquée comme lue puis on suit son lien. */
    public function lire(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['lien'] ?? route('notifications.index'));
    }

    public function toutLire(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }
}
