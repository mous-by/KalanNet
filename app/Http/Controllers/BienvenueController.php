<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Message de bienvenue de l'Admin d'une nouvelle école : vu une seule fois. */
class BienvenueController extends Controller
{
    public function vue()
    {
        // Le support connecté au compte (accès d'assistance) ne consomme pas le message.
        if (!session()->has('assistance')) {
            DB::table('utilisateurs')->where('idUtilisateur', Auth::id())->whereNull('bienvenue_vue_at')->update(['bienvenue_vue_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
