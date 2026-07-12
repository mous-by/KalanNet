<?php

namespace App\Services\Kalanbot\Tools\Eleves;

use App\Models\Eleve;
use App\Models\User;
use App\Services\Kalanbot\Tools\AbstractKalanbotTool;

/**
 * Aucune méthode existante de EleveController ne permet une recherche libre par nom
 * sur toute l'école (index()/dossiers() exigent id_classe+id_annee au préalable).
 * Cet outil comble ce manque pour la résolution floue de noms ("Quel Ibrahim ?"),
 * en s'appuyant uniquement sur le scope école automatique du modèle Eleve (aucune
 * logique métier de contrôleur à réutiliser ici, juste une lecture).
 */
class RechercherElevesTool extends AbstractKalanbotTool
{
    public function name(): string
    {
        return 'eleves_rechercher';
    }

    public function module(): string
    {
        return 'eleves';
    }

    public function description(): string
    {
        return "Rechercher des élèves actifs par nom, prénom ou matricule (recherche approximative), dans "
            . "toute l'école ou filtrée par classe. À utiliser systématiquement avant toute action sur un élève "
            . "si son identifiant n'est pas déjà connu avec certitude, et pour désambiguïser quand plusieurs "
            . "élèves portent un nom proche.";
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'recherche' => ['type' => 'STRING', 'description' => "Nom, prénom ou matricule (partiel) à rechercher."],
                'id_classe' => ['type' => 'INTEGER', 'description' => 'Filtrer par classe (optionnel).'],
                'id_annee' => ['type' => 'INTEGER', 'description' => "Filtrer par année scolaire (optionnel)."],
            ],
            'required' => ['recherche'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'recherche' => 'required|string|min:2|max:100',
            'id_classe' => 'nullable|integer',
            'id_annee' => 'nullable|integer',
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_apercu');
    }

    public function confirmationMessage(array $args, User $user): string
    {
        return '';
    }

    public function execute(array $args, User $user): array
    {
        $validated = $this->validateArgs($args);
        $search = $validated['recherche'];

        $eleves = Eleve::with('classe')
            ->where('etat_dossier', 0)
            ->when($validated['id_classe'] ?? null, fn ($q, $value) => $q->where('id_classe', $value))
            ->when($validated['id_annee'] ?? null, fn ($q, $value) => $q->where('id_annee', $value))
            ->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            })
            ->orderBy('prenom_eleve')
            ->orderBy('nom_eleve')
            ->limit(15)
            ->get();

        $results = $eleves->map(fn (Eleve $eleve) => [
            'id_eleve' => $eleve->id_eleve,
            'nom_complet' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
            'matricule' => $eleve->matricule,
            'classe' => optional($eleve->classe)->nom_classe,
            'id_classe' => $eleve->id_classe,
            'id_annee' => $eleve->id_annee,
            'genre' => $eleve->genre_eleve,
        ])->values()->all();

        return [
            'success' => true,
            'message' => count($results) === 0
                ? "Aucun élève actif ne correspond à « {$search} »."
                : count($results) . " élève(s) trouvé(s) pour « {$search} ».",
            'data' => ['eleves' => $results],
        ];
    }
}
