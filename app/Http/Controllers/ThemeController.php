<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ThemeController extends Controller
{
    /**
     * Store the selected theme for the authenticated user.
     * Expected JSON payload: { "theme": "light" }
     */
    public function store(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:bleu-sombre,light,dark,vert,violet,rouge,orange',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->theme_preference = $request->input('theme');
            $user->save();
        }

        return Response::json(['status' => 'ok']);
    }
}
?>
