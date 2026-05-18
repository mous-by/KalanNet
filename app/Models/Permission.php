<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission', 'permission_id', 'user_id');
    }

    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = str_replace([' ', '-'], '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    }

    public static function splitName(string $permissionName): array
    {
        $permissionName = self::normalizeName($permissionName);

        $compositeModules = [
            'assistant_ia',
            'classes_officielles',
            'status_controles',
            'annees_scolaires',
            'types_notes',
            'dossiers_eleves',
        ];

        foreach ($compositeModules as $prefix) {
            if ($permissionName === $prefix) {
                return [self::canonicalModuleName($prefix), 'acces'];
            }

            $prefixWithSeparator = $prefix . '_';
            if (str_starts_with($permissionName, $prefixWithSeparator)) {
                $action = substr($permissionName, strlen($prefixWithSeparator));
                return [self::canonicalModuleName($prefix), $action !== '' ? $action : 'acces'];
            }
        }

        $parts = explode('_', $permissionName, 2);

        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return [self::canonicalModuleName($parts[0]), $parts[1]];
        }

        return ['autres', $permissionName];
    }

    public static function canonicalModuleName(string $module): string
    {
        $module = self::normalizeName($module);

        $aliases = [
            'inscription' => 'inscriptions',
            'planification' => 'planifications',
            'programme' => 'programmes',
            'profile' => 'profiles',
        ];

        return $aliases[$module] ?? $module;
    }

    public static function moduleDisplayLabel(string $module): string
    {
        $module = self::canonicalModuleName($module);

        $labels = [
            'assistant_ia' => 'Assistant IA',
            'inscriptions' => 'Inscriptions',
            'planifications' => 'Planifications',
            'programmes' => 'Programmes',
            'profiles' => 'Profils',
            'annees_scolaires' => 'Années scolaires',
            'types_notes' => 'Types de notes',
            'status_controles' => 'Statuts de contrôle',
            'classes_officielles' => 'Classes officielles',
            'dossiers_eleves' => 'Dossiers élèves',
            'autres' => 'Autres',
        ];

        return $labels[$module] ?? ucwords(str_replace('_', ' ', $module));
    }

    public static function groupedByModule()
    {
        $permissions = self::query()
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $grouped = [];

        foreach ($permissions as $permission) {
            $name = self::normalizeName($permission->name);
            [$module, $action] = self::splitName($name);

            $grouped[$module] ??= [];
            $grouped[$module][] = (object) [
                'id' => $permission->id,
                'name' => $name,
                'module' => $module,
                'module_display' => self::moduleDisplayLabel($module),
                'action' => $action,
            ];
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($grouped as $module => $items) {
            usort($items, fn ($a, $b) => strnatcasecmp($a->action, $b->action));
            $grouped[$module] = $items;
        }

        return $grouped;
    }
}
