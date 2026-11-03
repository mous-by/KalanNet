<?php

namespace App\Console\Commands;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Support\Ordres;
use App\Support\RapportSuccinctDonnees;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rappel de fin octobre (cron) : les établissements abonnés, pour chaque fiche (CAP, académie), qui n'ont pas remis leur rapport
 * succinct. Une seule fois par compte et par année, même si la commande est relancée.
 */
class RappelRapportSuccinct extends Command
{
    protected $signature = 'rapports-succincts:rappel';

    protected $description = 'Rappelle aux écoles de remettre leur rapport succinct de rentrée';

    public function handle(): int
    {
        $envoyes = 0;
        foreach (['fondamental' => Ordres::SOUS_CAP, 'secondaire' => RapportSuccinctDonnees::SECONDAIRE] as $portee => $ordres) {
            $ecoles = DB::table('ecole')
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'ecole.idEcole')->whereIn('classe.ordreEnseignement', $ordres))
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('abonnements')->whereColumn('abonnements.ecole_id', 'ecole.idEcole')->where('abonnements.statut', 'actif')
                    ->where(fn ($q) => $q->whereNull('abonnements.fin_at')->orWhere('abonnements.fin_at', '>=', today())))
                ->pluck('idEcole');

            foreach ($ecoles as $idEcole) {
                $annee = AnneeScolaire::enCours((int) $idEcole);
                if (!$annee || DB::table('rapports_succincts')->where('id_ecole', $idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('portee', $portee)->where('statut', '!=', 'brouillon')->exists()) {
                    continue;
                }

                $lien = '/rapport-succinct?annee='.$annee->id_anneeScolaire.($portee === 'secondaire' ? '&portee=secondaire' : '');
                $comptes = DB::table('utilisateurs')->where('idEcole', $idEcole)->where('droit', 'Admin')->where('statut', 1)->pluck('idUtilisateur');
                foreach ($comptes as $compte) {
                    if (AppNotification::where('user_id', $compte)->where('type', 'rapport_succinct_rappel')->where('link', $lien)->exists()) {
                        continue;
                    }
                    AppNotification::create([
                        'user_id' => $compte, 'type' => 'rapport_succinct_rappel', 'title' => 'Rapport succinct à remettre',
                        'message' => 'Votre rapport succinct de rentrée '.$annee->annee.' n\'a pas encore été remis à votre '.($portee === 'secondaire' ? 'académie' : 'CAP').'.',
                        'link' => $lien,
                    ]);
                    $envoyes++;
                }
            }
        }

        $this->info($envoyes.' rappel(s) envoyé(s).');

        return self::SUCCESS;
    }
}
