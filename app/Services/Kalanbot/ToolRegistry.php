<?php

namespace App\Services\Kalanbot;

use App\Models\User;
use App\Services\Kalanbot\Contracts\KalanbotToolInterface;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

/**
 * Découvre automatiquement tous les outils Kalanbot (app/Services/Kalanbot/Tools/**),
 * et ne transmet à Gemini que ceux autorisés pour l'utilisateur connecté.
 */
class ToolRegistry
{
    private const NAMESPACE = 'App\\Services\\Kalanbot\\Tools\\';

    /** @var Collection<int, KalanbotToolInterface>|null */
    private ?Collection $tools = null;

    /** @return Collection<int, KalanbotToolInterface> Tous les outils déclarés, quel que soit le rôle. */
    public function all(): Collection
    {
        if ($this->tools !== null) {
            return $this->tools;
        }

        $directory = app_path('Services/Kalanbot/Tools');
        $instances = collect();

        if (!is_dir($directory)) {
            return $this->tools = $instances;
        }

        foreach ((new Finder())->files()->name('*Tool.php')->in($directory) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = self::NAMESPACE . $relative;

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->implementsInterface(KalanbotToolInterface::class)) {
                continue;
            }

            $instances->push(app($class));
        }

        return $this->tools = $instances->sortBy('name')->values();
    }

    /** Outils autorisés pour l'utilisateur, tous modules confondus. */
    public function authorizedFor(User $user): Collection
    {
        return $this->all()->filter(fn (KalanbotToolInterface $tool) => $tool->authorize($user))->values();
    }

    /** Outils autorisés pour l'utilisateur, restreints à un module (routage en 2 étapes). */
    public function authorizedForModule(User $user, string $module): Collection
    {
        return $this->authorizedFor($user)->filter(fn (KalanbotToolInterface $tool) => $tool->module() === $module)->values();
    }

    /** Liste des modules disponibles pour l'utilisateur (pour l'étape de routage). */
    public function modulesFor(User $user): array
    {
        return $this->authorizedFor($user)->pluck('module')->unique()->values()->all();
    }

    public function find(string $name): ?KalanbotToolInterface
    {
        return $this->all()->first(fn (KalanbotToolInterface $tool) => $tool->name() === $name);
    }

    /** Déclarations Gemini (functionDeclarations) pour un ensemble d'outils déjà filtré. */
    public function declarationsFor(Collection $tools): array
    {
        return $tools->map(fn (KalanbotToolInterface $tool) => $tool->declaration())->values()->all();
    }
}
