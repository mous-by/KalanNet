<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité de toutes les réponses (site et API) :
 *  - pas d'affichage de KalanNet dans le cadre d'un autre site (piège au clic) ; les aperçus internes (bulletins,
 *    cartes, emploi du temps) restent possibles, ils sont sur le même site ;
 *  - le navigateur n'interprète jamais un fichier autrement que selon son type déclaré ;
 *  - adresse complète des pages jamais transmise aux autres sites ;
 *  - caméra et micro réservés à KalanNet (photo du reçu, dictée de l'assistant), pas de géolocalisation ;
 *  - HTTPS imposé au navigateur pendant un an, seulement quand la page est déjà servie en HTTPS.
 */
class EnTetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $entetes = $response->headers;

        $entetes->set('X-Frame-Options', 'SAMEORIGIN', false);
        $entetes->set('X-Content-Type-Options', 'nosniff', false);
        $entetes->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $entetes->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()', false);
        if ($request->isSecure()) {
            $entetes->set('Strict-Transport-Security', 'max-age=31536000', false);
        }

        return $response;
    }
}
