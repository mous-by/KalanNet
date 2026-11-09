<?php

namespace App\Support;

use App\Mail\NouvelleConnexion;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Alerte « Nouvelle connexion détectée » : quand un compte se connecte depuis un appareil qu'il n'a jamais utilisé, son
 * titulaire est prévenu par e-mail (s'il en a un) et dans ses notifications KalanNet. L'appareil se reconnaît à son
 * système, son navigateur ou son nom d'application, pas à l'adresse IP, qui change sans cesse sur les réseaux mobiles.
 * Le premier appareil d'un compte est seulement enregistré : la mise en ligne ne déclenche pas d'alerte pour tous.
 */
class AlerteConnexion
{
    /** @return array{appareil: string, empreinte: string} */
    public static function appareil(?string $userAgent, string $canal, ?string $nomApplication = null): array
    {
        $ua = (string) $userAgent;
        $systeme = match (true) {
            (bool) preg_match('/iPhone OS ([\d_]+)/', $ua, $m) => ['iPhone', 'iOS '.str_replace('_', '.', $m[1])],
            (bool) preg_match('/iPad.*OS ([\d_]+)/', $ua, $m) => ['iPad', 'iPadOS '.str_replace('_', '.', $m[1])],
            (bool) preg_match('/Android ([\d.]+)/', $ua, $m) => ['Android', 'Android '.$m[1]],
            str_contains($ua, 'Windows') => ['Windows', 'Windows'],
            str_contains($ua, 'Mac OS X') => ['Mac', 'macOS'],
            str_contains($ua, 'CrOS') => ['Chromebook', 'ChromeOS'],
            str_contains($ua, 'Linux') => ['Linux', 'Linux'],
            default => ['Appareil', 'système inconnu'],
        };
        $navigateur = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'navigateur inconnu',
        };

        if ($canal === 'mobile') {
            $nom = trim((string) $nomApplication) !== '' && $nomApplication !== 'mobile' ? trim((string) $nomApplication) : $systeme[0];
            $appareil = 'Application KalanNet sur '.$nom;
            // Une même installation garde son nom ; le système seul suffit quand l'application n'en donne pas.
            $empreinte = 'mobile|'.mb_strtolower($nom);
        } else {
            $appareil = $navigateur.' sur '.$systeme[0].' ('.$systeme[1].')';
            // Sans les numéros de version : une mise à jour du navigateur n'est pas un nouvel appareil.
            $empreinte = 'web|'.$systeme[0].'|'.$navigateur;
        }

        return ['appareil' => mb_substr($appareil, 0, 190), 'empreinte' => hash('sha256', $empreinte)];
    }

    public static function signaler(User $user, Request $request, string $canal): void
    {
        try {
            $info = self::appareil($request->userAgent(), $canal, $request->input('device_name'));
            $connus = DB::table('connexions_appareils')->where('user_id', $user->idUtilisateur);
            $deja = (clone $connus)->where('empreinte', $info['empreinte'])->exists();
            $premier = !(clone $connus)->exists();

            DB::table('connexions_appareils')->updateOrInsert(
                ['user_id' => $user->idUtilisateur, 'empreinte' => $info['empreinte']],
                ['appareil' => $info['appareil'], 'canal' => $canal, 'ip' => $request->ip(), 'derniere_connexion_at' => now()]
                    + ($deja ? [] : ['premiere_connexion_at' => now()]),
            );
            if ($deja || $premier) {
                return;
            }

            $quand = now();
            AppNotification::create([
                'user_id' => $user->idUtilisateur, 'type' => 'securite_connexion', 'title' => 'Nouvelle connexion détectée',
                'message' => 'Connexion depuis un nouvel appareil : '.$info['appareil'].' (IP '.$request->ip().'), le '.$quand->format('d/m/Y à H:i').' UTC. Ce n\'était pas vous ? Changez votre mot de passe.',
                'link' => route('profile.edit', [], false),
            ]);

            $email = trim((string) $user->email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail = new NouvelleConnexion((string) $user->nomPrenom, $quand->format('d/m/Y H:i').' UTC', (string) $request->ip(), $info['appareil'], (string) $request->userAgent());
                // Envoyé une fois la page affichée : la connexion n'attend pas le serveur d'e-mails. Une seule fois,
                // même si l'application traite d'autres requêtes ensuite.
                $etat = new \stdClass;
                $etat->envoye = false;
                app()->terminating(function () use ($email, $mail, $etat) {
                    if ($etat->envoye) {
                        return;
                    }
                    $etat->envoye = true;
                    try {
                        Mail::to($email)->send($mail);
                    } catch (\Throwable $e) {
                        Log::warning('Alerte de connexion non envoyée : '.$e->getMessage());
                    }
                });
            }
        } catch (\Throwable $e) {
            // L'alerte ne doit jamais empêcher de se connecter.
            Log::warning('Alerte de connexion : '.$e->getMessage());
        }
    }
}
