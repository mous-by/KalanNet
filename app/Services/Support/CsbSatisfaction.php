<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Publication des avis de satisfaction sur le site de Computer Service Barry, à la demande du SupAdmin :
 * un avis (témoignage avec nom, fonction et école, seulement si son auteur l'a accepté) ou le rapport chiffré.
 * L'envoi est immédiat : CSB l'affiche dès sa réponse.
 */
class CsbSatisfaction
{
    public static function configure(): bool
    {
        return CsbEcoles::configure();
    }

    /** Met en ligne (ou retire) le témoignage d'un avis. @return string|null adresse de la page sur le site */
    public function publierAvis(object $avis, bool $enLigne): ?string
    {
        if ($enLigne && (!$avis->accord_publication || trim((string) $avis->commentaire) === '')) {
            throw new \InvalidArgumentException('Seul un avis avec un commentaire et l\'accord de son auteur peut être publié.');
        }

        $auteur = DB::table('utilisateurs')->where('idUtilisateur', $avis->user_id)->first(['nomPrenom', 'fonction']);
        $ecole = $avis->id_ecole ? DB::table('ecole')->where('idEcole', $avis->id_ecole)->first(['nomEcole', 'adresse']) : null;

        $reponse = $this->http()->post($this->url('temoignages'), [
            'ref' => 'avis-'.$avis->id,
            'publie' => $enLigne,
            'auteur' => (string) ($auteur->nomPrenom ?? ''),
            'fonction' => $auteur->fonction ?: null,
            'organisation' => $ecole ? trim($ecole->nomEcole.($ecole->adresse ? ', '.$ecole->adresse : '')) : null,
            'note' => (int) $avis->note,
            'texte' => trim((string) $avis->commentaire),
            'accord_at' => (string) ($avis->updated_at ?? $avis->created_at),
        ])->throw();

        DB::table('avis_satisfaction')->where('id', $avis->id)->update(['publie_at' => $enLigne ? now() : null]);

        return $reponse->json('url');
    }

    /**
     * Publie le rapport chiffré (moyenne, part de satisfaits, répartition, points forts) : il remplace le précédent.
     *
     * @param  array<string, mixed>  $rapport  résultat de Satisfaction::rapport()
     * @return string|null adresse de la page sur le site
     */
    public function publierRapport(array $rapport, int $publiePar): ?string
    {
        if (($rapport['total'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Aucun avis sur cette période : rien à publier.');
        }

        $contenu = [
            'application' => 'KalanNet',
            'total' => $rapport['total'],
            'moyenne' => $rapport['moyenne'],
            'pct_satisfaits' => $rapport['pct_satisfaits'],
            'nb_ecoles' => $rapport['nb_ecoles'],
            'repartition' => $rapport['repartition'],
            'points_forts' => array_slice(array_column($rapport['points_forts'], 'libelle'), 0, 3),
            'depuis' => $rapport['depuis'],
            'jusqua' => $rapport['jusqua'],
        ];

        $reponse = $this->http()->post($this->url('satisfaction'), $contenu)->throw();

        DB::table('satisfaction_publications')->insert([
            'contenu' => json_encode($contenu, JSON_UNESCAPED_UNICODE),
            'publie_par' => $publiePar,
            'publie_at' => now(),
        ]);

        return $reponse->json('url');
    }

    private function url(string $quoi): string
    {
        return rtrim((string) config('services.csb_support.url'), '/').'/api/v1/references/'.$quoi;
    }

    private function http()
    {
        return Http::withToken((string) config('services.csb_support.token'))->acceptJson()->timeout(20);
    }
}
