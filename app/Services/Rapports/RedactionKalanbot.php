<?php

namespace App\Services\Rapports;

use App\Support\RapportSecondaireDefinitions as Def;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Kalanbot propose un brouillon pour une rubrique de la partie administrative des rapports du secondaire, à partir des
 * chiffres du rapport. Aucun nom de personne n'est envoyé : les listes nominatives (personnel, admis…), l'administration
 * et les coordonnées sont écartées. Ce qui ne se déduit pas des chiffres reste « [à compléter] ». L'établissement relit
 * et corrige : rien n'est enregistré sans lui.
 */
class RedactionKalanbot
{
    private const CONSIGNE = <<<'TXT'
Tu es Kalanbot, l'assistant de KalanNet. Tu aides le chef d'un établissement d'enseignement secondaire du Mali à rédiger
une rubrique de la partie administrative de son rapport officiel destiné à l'académie d'enseignement.
Règles :
- Rédige en français administratif, clair et sobre, au nom de l'établissement (« notre établissement », « nous »).
- Appuie-toi UNIQUEMENT sur les données fournies. N'invente aucun chiffre, aucun nom, aucun événement, aucun partenaire.
- Cite les chiffres utiles (effectifs, pourcentages que tu calcules à partir d'eux, arrondis à une décimale, comparaisons
  filles / garçons).
- Ne confonds pas les tableaux : lis le titre de chaque tableau et les précisions données avec les données.
- Ce que les données ne disent pas (faits vécus, difficultés concrètes, dates, partenaires, actions menées) : écris
  « [à compléter : …] » en précisant ce qu'il faut y mettre.
- Pas de titre ni de formule de politesse ; pas de Markdown (ni astérisques, ni dièses) ; des paragraphes courts.
- Entre 120 et 350 mots.
TXT;

    /** Tableaux sans nom de personne qui peuvent servir de contexte (en plus des tableaux de nombres). */
    private const TEXTES_UTILES = ['T4', 'T25', 'T26', 'T27'];

    /**
     * @return array{ok: bool, texte?: string, message?: string}
     */
    public function proposer(string $type, string $rubrique, array $donnees, array $tableaux, string $etablissement, string $annee): array
    {
        $rubriques = Def::redaction($type);
        if (!isset($rubriques[$rubrique])) {
            return ['ok' => false, 'message' => 'Rubrique inconnue.'];
        }
        [$titre, $contenu] = $rubriques[$rubrique];

        $demande = "Rapport : ".Def::TYPES[$type]." {$annee}, établissement « {$etablissement} ».\n"
            ."Rubrique à rédiger : {$titre}.".($contenu ? " Elle doit traiter : {$contenu}" : '')."\n\n"
            .($type === 'rentree' ? "Précision : au rapport de rentrée, « passants » (T6) désigne les élèves qui ne redoublent pas cette année ; ce n'est pas un taux de réussite. Les résultats de fin d'année ne sont pas dans ce rapport, seulement ceux du baccalauréat de la session écoulée (T10, T11).\n" : '')
            ."Données du rapport :\n".self::resume($donnees, $tableaux)
            .self::autresRubriques($donnees, $rubriques, $rubrique);

        $texte = $this->appeler(self::CONSIGNE, $demande);
        if ($texte === null) {
            return ['ok' => false, 'message' => 'Kalanbot est momentanément indisponible. Réessayez dans un instant ; votre texte n\'a pas été modifié.'];
        }

        return ['ok' => true, 'texte' => self::nettoyer($texte)];
    }

    /** Résumé chiffré du rapport, sans aucun nom de personne. */
    public static function resume(array $donnees, array $tableaux): string
    {
        $lignes = [];
        $exclus = ['telephone', 'email', 'bp', 'gps', 'autres'];
        foreach (Def::infos($donnees['type'] ?? 'rentree') as $champ => $libelle) {
            $valeur = $donnees['infos'][$champ] ?? null;
            if (filled($valeur) && !in_array($champ, $exclus, true)) {
                $lignes[] = "- {$libelle} : ".Str::limit((string) $valeur, 120);
            }
        }

        foreach ($tableaux as $tableau) {
            $texte = ($tableau['type'] ?? 'nombre') === 'texte';
            if (!empty($tableau['liste']) || ($texte && !in_array($tableau['code'], self::TEXTES_UTILES, true))) {
                continue; // listes nominatives, administration : jamais envoyées
            }
            $valeurs = $donnees['t'][$tableau['code']] ?? [];
            $colonnes = collect($tableau['colonnes'])->flatMap(fn ($g) => collect($g['cols'])->map(fn ($l) => trim(($g['libelle'] ? $g['libelle'].' ' : '').$l)))->all();
            $detail = [];
            foreach ($tableau['lignes'] as $ligne => $libelle) {
                $cases = collect($colonnes)->filter(fn ($l, $c) => filled($valeurs[$ligne][$c] ?? null) && ($valeurs[$ligne][$c] ?? 0) !== 0)
                    ->map(fn ($l, $c) => "{$l} = ".Str::limit((string) $valeurs[$ligne][$c], 80));
                if ($cases->isNotEmpty()) {
                    $detail[] = "  {$libelle} : ".$cases->implode(' ; ');
                }
            }
            if ($detail) {
                $lignes[] = ($tableau['numero'] ?? $tableau['code']).' '.$tableau['titre']." :\n".implode("\n", array_slice($detail, 0, 40));
            }
        }

        return $lignes ? implode("\n", $lignes) : '(aucune donnée saisie pour le moment)';
    }

    private static function autresRubriques(array $donnees, array $rubriques, string $rubrique): string
    {
        $deja = collect($rubriques)->except($rubrique)
            ->map(fn ($r, $cle) => filled($donnees['redaction'][$cle] ?? null) ? $r[0].' : '.Str::limit((string) $donnees['redaction'][$cle], 600) : null)
            ->filter();

        return $deja->isEmpty() ? '' : "\n\nRubriques déjà rédigées par l'établissement (pour rester cohérent, sans les répéter) :\n".$deja->implode("\n");
    }

    /** Sans Markdown : la case du formulaire est du texte simple. */
    private static function nettoyer(string $texte): string
    {
        $texte = preg_replace('/^\s*#+\s*/m', '', $texte);
        $texte = str_replace(['**', '__'], '', $texte);

        return trim(preg_replace("/\n{3,}/", "\n\n", $texte));
    }

    /** Appel au fournisseur configuré pour Kalanbot ; null en cas d'échec (jamais un message d'erreur pris pour du texte). */
    private function appeler(string $systeme, string $demande): ?string
    {
        $fournisseur = strtolower((string) config('services.assistant.provider', 'gemini'));
        $delai = max(20, (int) config('services.assistant.timeout', 20));

        try {
            if ($fournisseur === 'gemini') {
                $cle = config('services.assistant.gemini.key');
                if (!$cle) {
                    return null;
                }
                $modele = config('services.assistant.gemini.model', 'gemini-2.5-flash');
                $reponse = Http::timeout($delai)->withHeaders(['x-goog-api-key' => $cle])
                    ->post(rtrim((string) config('services.assistant.gemini.endpoint'), '/')."/models/{$modele}:generateContent", [
                        'systemInstruction' => ['parts' => [['text' => $systeme]]],
                        'contents' => [['role' => 'user', 'parts' => [['text' => $demande]]]],
                        'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 2048, 'thinkingConfig' => ['thinkingBudget' => 0]],
                    ]);

                return $reponse->successful() ? (trim((string) data_get($reponse->json(), 'candidates.0.content.parts.0.text')) ?: null) : null;
            }

            $messages = [['role' => 'system', 'content' => $systeme], ['role' => 'user', 'content' => $demande]];
            if ($fournisseur === 'ollama') {
                $reponse = Http::timeout($delai)->post(config('services.assistant.ollama.endpoint'), [
                    'model' => config('services.assistant.ollama.model', 'llama3'), 'stream' => false, 'messages' => $messages, 'options' => ['temperature' => 0.4],
                ]);

                return $reponse->successful() ? (trim((string) data_get($reponse->json(), 'message.content')) ?: null) : null;
            }

            $config = config("services.assistant.{$fournisseur}");
            if (empty($config['key'])) {
                return null;
            }
            $reponse = Http::timeout($delai)->withToken($config['key'])->acceptJson()
                ->post($config['endpoint'], ['model' => $config['model'], 'messages' => $messages, 'temperature' => 0.4, 'max_tokens' => 1500]);

            return $reponse->successful() ? (trim((string) data_get($reponse->json(), 'choices.0.message.content')) ?: null) : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
