<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    public function toggle(Request $request)
    {
        $this->authorizeSupAdminOnly();

        $data = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $etat = MaintenanceMode::current();
        $activation = !$etat->actif;
        $message = trim((string) ($data['message'] ?? ''));

        $etat->update([
            'actif' => $activation,
            'message' => $message !== '' ? $message : MaintenanceMode::DEFAULT_MESSAGE,
            'active_par' => $activation ? Auth::id() : $etat->active_par,
            'active_at' => $activation ? now() : $etat->active_at,
        ]);

        return back()->with('success', $activation
            ? 'Mode maintenance activé — l’application est désormais inaccessible aux autres utilisateurs.'
            : 'Mode maintenance désactivé — l’application est de nouveau accessible à tous.');
    }

    protected function authorizeSupAdminOnly(): void
    {
        if (Auth::user()->droit !== 'SupAdmin') {
            abort(403);
        }
    }
}
