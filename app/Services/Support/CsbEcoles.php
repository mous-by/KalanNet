<?php

namespace App\Services\Support;

use App\Models\Ecole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Bande « Ils nous font confiance » du site de CSB : nom, adresse et logo des écoles dont l'abonnement est actif et
 * dont l'Admin n'a pas retiré l'affichage (Mon profil). Rien d'autre ne sort de KalanNet.
 *
 * Une école créée ou modifiée, un abonnement activé : envoi juste après la réponse, jamais bloquant (CSB injoignable =
 * erreur journalisée). `php artisan csb:ecoles` (cron) envoie la liste complète : il rattrape les envois manqués et
 * retire les écoles dont l'abonnement a expiré.
 */
class CsbEcoles
{
    /** @var array<int, true> */
    private static array $enAttente = [];

    public static function configure(): bool
    {
        return filled(config('services.csb_support.url')) && filled(config('services.csb_support.token'));
    }

    /** Une seule requête par école, à la fin de la requête en cours (école et abonnement sont alors enregistrés). */
    public static function planifier(int $idEcole): void
    {
        if (!self::configure() || isset(self::$enAttente[$idEcole])) {
            return;
        }
        self::$enAttente[$idEcole] = true;

        \Illuminate\Support\defer(function () use ($idEcole) {
            unset(self::$enAttente[$idEcole]);
            try {
                app(self::class)->envoyerEcole($idEcole);
            } catch (Throwable $e) {
                report($e);
            }
        }, 'csb-ecole-'.$idEcole);
    }

    public function envoyerEcole(int $idEcole): void
    {
        $ecole = Ecole::withoutGlobalScopes()->find($idEcole);
        $ligne = $ecole
            ? $this->ligne($ecole, $this->visibles(collect([$idEcole]))->contains($idEcole))
            : ['ref' => (string) $idEcole, 'nom' => '-', 'visible' => false];

        $this->envoyer([$ligne], false, $ecole ? [$ligne['ref'] => $ecole] : []);
    }

    /** Liste complète : CSB retire de sa bande toute école absente de cette liste. @return int écoles envoyées */
    public function envoyerTout(): int
    {
        $ids = $this->visibles();
        $ecoles = Ecole::withoutGlobalScopes()->whereIn('idEcole', $ids)->get();
        $lignes = $ecoles->map(fn (Ecole $e) => $this->ligne($e, true))->values()->all();

        $this->envoyer($lignes, true, $ecoles->keyBy(fn (Ecole $e) => (string) $e->idEcole)->all());

        return count($lignes);
    }

    /**
     * Ce qui serait affiché, sans rien envoyer (à contrôler avant le premier envoi : pas d'école de démonstration).
     *
     * @return array{total: int, echantillon: list<array{0: string, 1: string, 2: string}>}
     */
    public function apercu(): array
    {
        $ids = $this->visibles();
        $echantillon = Ecole::withoutGlobalScopes()->whereIn('idEcole', $ids)->inRandomOrder()->limit(30)->get()
            ->map(fn (Ecole $e) => [(string) $e->nomEcole, (string) $e->adresse, $this->cheminLogo($e) ? 'oui' : 'non'])->all();

        return ['total' => $ids->count(), 'echantillon' => $echantillon];
    }

    /**
     * Abonnement actif (même règle que SubscriptionGate) et affichage non retiré.
     *
     * @return Collection<int, int>
     */
    private function visibles(?Collection $parmi = null): Collection
    {
        return DB::table('ecole')->where('afficher_sur_csb', true)
            ->when($parmi, fn ($q) => $q->whereIn('idEcole', $parmi))
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('abonnements')->whereColumn('abonnements.ecole_id', 'ecole.idEcole')->where('abonnements.statut', 'actif')
                ->where(fn ($q) => $q->whereNull('abonnements.fin_at')->orWhere('abonnements.fin_at', '>=', today())))
            ->pluck('idEcole')->map(fn ($id) => (int) $id);
    }

    /** @return array<string, mixed> */
    private function ligne(Ecole $ecole, bool $visible): array
    {
        $logo = $this->cheminLogo($ecole);

        return ['ref' => (string) $ecole->idEcole, 'nom' => (string) $ecole->nomEcole, 'adresse' => $ecole->adresse ?: null, 'logo_hash' => $logo ? md5_file($logo) : null, 'visible' => $visible];
    }

    private function cheminLogo(Ecole $ecole): ?string
    {
        $chemin = $ecole->logoEcole ? public_path(ltrim($ecole->logoEcole, '/')) : null;

        return $chemin && is_file($chemin) ? $chemin : null;
    }

    /** @param array<string, Ecole> $ecoles */
    private function envoyer(array $lignes, bool $complet, array $ecoles): void
    {
        $base = rtrim((string) config('services.csb_support.url'), '/').'/api/v1/references/ecoles';
        $http = fn () => Http::withToken((string) config('services.csb_support.token'))->acceptJson()->timeout(20);

        $reponse = $http()->post($base, ['ecoles' => $lignes, 'complet' => $complet])->throw();

        foreach ((array) $reponse->json('logos_a_envoyer', []) as $ref) {
            $chemin = isset($ecoles[$ref]) ? $this->cheminLogo($ecoles[$ref]) : null;
            if ($chemin) {
                $http()->attach('logo', fopen($chemin, 'r'), basename($chemin))->post($base.'/'.rawurlencode((string) $ref).'/logo', ['logo_hash' => md5_file($chemin)])->throw();
            }
        }
    }
}
