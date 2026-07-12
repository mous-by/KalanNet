<?php

namespace App\Services\Kalanbot\Tools;

use App\Services\Kalanbot\Contracts\KalanbotToolInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base commune des outils Kalanbot : construit la déclaration Gemini à partir
 * de description()/parametersSchema(), et fournit les helpers pour appeler
 * les contrôleurs existants sans dupliquer leur logique.
 */
abstract class AbstractKalanbotTool implements KalanbotToolInterface
{
    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function requiresDoubleConfirmation(): bool
    {
        return false;
    }

    public function declaration(): array
    {
        $parameters = $this->parametersSchema();

        // Gemini exige un objet JSON pour "properties" ; un tableau PHP vide se
        // sérialise en liste JSON ([]) et fait échouer TOUT l'appel Gemini (l'API
        // rejette la liste complète de déclarations si une seule est invalide).
        if (isset($parameters['properties']) && $parameters['properties'] === []) {
            $parameters['properties'] = (object) [];
        }

        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => $parameters,
        ];
    }

    /**
     * Valide les arguments reçus de Gemini avec les règles Laravel de l'outil.
     * Lève ValidationException (interceptée par callController) si invalide.
     */
    protected function validateArgs(array $args): array
    {
        return Validator::make($args, $this->validationRules())->validate();
    }

    /** Construit une requête HTTP interne (POST) pour appeler une méthode de contrôleur existante. */
    protected function makeRequest(array $args): Request
    {
        return Request::create('/kalanbot-internal', 'POST', $args);
    }

    /** Variante GET, pour les contrôleurs qui distinguent le verbe HTTP (ex: TimetableController::index). */
    protected function makeGetRequest(array $query = []): Request
    {
        return Request::create('/kalanbot-internal', 'GET', $query);
    }

    /** Extrait les données brutes (sans rendu HTML) d'une vue renvoyée par un contrôleur. */
    protected function extractViewData(View $view): array
    {
        return $view->getData();
    }

    /** Extrait le tableau JSON d'une réponse renvoyée par un contrôleur. */
    protected function extractJsonData(JsonResponse $response): array
    {
        return $response->getData(true);
    }

    /**
     * Exécute un appel à une méthode de contrôleur existante et normalise
     * le résultat/les erreurs (validation, autorisation, introuvable) pour Gemini.
     */
    protected function callController(callable $call): array
    {
        try {
            $result = $call();

            // Certaines méthodes de contrôleur signalent un refus métier via
            // redirect()->with('error', ...) plutôt qu'une exception (ex: interdiction
            // de modifier/supprimer un émargement déjà validé). Sans cette détection,
            // un tel refus serait rapporté à tort comme un succès.
            if ($result instanceof RedirectResponse) {
                $error = session('error');
                if ($error) {
                    session()->forget('error');

                    return ['success' => false, 'error' => 'refused', 'message' => (string) $error];
                }

                // Autre mécanisme de refus métier utilisé par certains contrôleurs :
                // back()->withErrors($message) (ex: AbonnementController), distinct de
                // ValidationException et de with('error', ...).
                $errorBag = session('errors');
                if ($errorBag && method_exists($errorBag, 'any') && $errorBag->any()) {
                    session()->forget('errors');

                    return ['success' => false, 'error' => 'refused', 'message' => implode(' ', $errorBag->all())];
                }

                session()->forget('success');
            }

            return ['success' => true, 'result' => $result];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => 'validation',
                'message' => $this->formatValidationMessage($e),
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();

            return [
                'success' => false,
                'error' => $status === 403 ? 'forbidden' : ($status === 404 ? 'not_found' : 'refused'),
                'message' => match (true) {
                    $status === 403 => "Vous n'avez pas la permission d'effectuer cette action.",
                    $status === 404 => 'Élément introuvable.',
                    default => $e->getMessage() ?: 'Action refusée par l\'application.',
                },
            ];
        } catch (ModelNotFoundException $e) {
            // findOrFail() est utilisé massivement par les contrôleurs enveloppés ; hors du
            // cycle HTTP normal (appel direct, sans routeur), Laravel ne la convertit pas
            // automatiquement en 404 : on la traite donc explicitement ici.
            return [
                'success' => false,
                'error' => 'not_found',
                'message' => 'Élément introuvable (identifiant invalide ou hors de votre périmètre).',
            ];
        }
    }

    /**
     * Certaines clés de traduction Laravel (validation.*) ne sont pas résolues dans ce
     * projet (lang/fr/validation.php absent) : les messages reviennent alors bruts
     * ("validation.required"). On détecte ce cas et on retombe sur les noms de champs,
     * plutôt que de renvoyer un message incompréhensible à l'utilisateur.
     */
    private function formatValidationMessage(ValidationException $e): string
    {
        $messages = $e->validator->errors()->all();
        $untranslated = collect($messages)->contains(fn ($m) => str_starts_with($m, 'validation.'));

        if ($untranslated) {
            $fields = implode(', ', $e->validator->errors()->keys());

            return "Certaines informations sont manquantes ou invalides : {$fields}.";
        }

        return implode(' ', $messages);
    }
}
