<?php

namespace App\Services\Kalanbot\Tools\ResultatsNationaux;

use App\Http\Controllers\ResultatNationalController;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

class ConsulterResultatsNationauxTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'resultats_nationaux_consulter';
    }

    public function module(): string
    {
        return 'resultats_nationaux';
    }

    public function description(): string
    {
        return "Consulter les résultats aux examens nationaux (DEF ou BAC) d'une classe pour une année scolaire.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'id_classe' => ['type' => 'INTEGER'],
                'id_annee' => ['type' => 'INTEGER'],
                'niveau_examen' => ['type' => 'STRING', 'description' => 'DEF ou BAC (déduit de la classe si omis).'],
            ],
            'required' => ['id_classe', 'id_annee'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'id_classe' => 'required|integer',
            'id_annee' => 'required|integer',
            'niveau_examen' => 'nullable|string|in:DEF,BAC',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
            'resultats_nationaux_apercu', 'resultats nationaux_apercu',
            'resultats_def_terminal_apercu', 'reinscriptions_apercu', 'inscriptions_reinscrire',
        ]);
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $request = $this->makeGetRequest($validated);

        $outcome = $this->callController(fn () => app(ResultatNationalController::class)->index($request));
        if (!$outcome['success']) {
            return $outcome;
        }

        $data = $this->extractViewData($outcome['result']);
        $eleves = collect($data['eleves'] ?? []);
        $resultats = collect($data['resultats'] ?? []);

        $rows = $eleves->map(function ($eleve) use ($resultats) {
            $resultat = $resultats->get($eleve->id_eleve);

            return [
                'id_eleve' => $eleve->id_eleve,
                'nom_complet' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'matricule' => $eleve->matricule,
                'decision' => $resultat->decision ?? null,
                'moyenne' => $resultat->moyenne ?? null,
                'observation' => $resultat->observation ?? null,
            ];
        })->values()->all();

        return [
            'success' => true,
            'message' => count($rows) . ' élève(s), niveau ' . ($data['niveauExamen'] ?? '?') . '.',
            'data' => ['niveau_examen' => $data['niveauExamen'] ?? null, 'resultats' => $rows],
        ];
    }
}
