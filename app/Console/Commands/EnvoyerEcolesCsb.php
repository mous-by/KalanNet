<?php

namespace App\Console\Commands;

use App\Services\Support\CsbEcoles;
use Illuminate\Console\Command;

/** Liste complète des écoles à afficher sur le site de CSB (cron : rattrape les envois manqués, retire les expirées). */
class EnvoyerEcolesCsb extends Command
{
    protected $signature = 'csb:ecoles {--apercu : Affiche le nombre d\'écoles et un échantillon, sans rien envoyer}';

    protected $description = 'Envoie à CSB les écoles à afficher sur son site';

    public function handle(CsbEcoles $ecoles): int
    {
        if ($this->option('apercu')) {
            $apercu = $ecoles->apercu();
            $this->info($apercu['total'].' école(s) seraient affichées sur le site de CSB. Rien n\'a été envoyé.');
            $this->table(['École', 'Adresse', 'Logo'], $apercu['echantillon']);

            return self::SUCCESS;
        }

        if (!CsbEcoles::configure()) {
            $this->warn('CSB_SUPPORT_URL et CSB_SUPPORT_TOKEN ne sont pas renseignés : rien envoyé.');

            return self::SUCCESS;
        }

        try {
            $this->info($ecoles->envoyerTout().' école(s) envoyée(s) à CSB.');
        } catch (\Throwable $e) {
            // Logos en grand nombre (limite de débit) ou CSB injoignable : le prochain passage reprend là où il s'est arrêté.
            $this->error('Envoi interrompu : '.substr($e->getMessage(), 0, 200));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
