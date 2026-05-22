<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = DB::table('permissions')->select('id', 'name')->orderBy('id')->get();
        $groups = [];

        foreach ($permissions as $permission) {
            $groups[$this->canonicalName($permission->name)][] = $permission;
        }

        foreach ($groups as $canonicalName => $items) {
            $canonical = collect($items)->firstWhere('name', $canonicalName);

            if (!$canonical) {
                $canonical = collect($items)->sortBy('id')->first();
                DB::table('permissions')
                    ->where('id', $canonical->id)
                    ->update(['name' => $canonicalName]);
            }

            foreach ($items as $duplicate) {
                if ((int) $duplicate->id === (int) $canonical->id) {
                    continue;
                }

                if (Schema::hasTable('user_permission')) {
                    $links = DB::table('user_permission')
                        ->where('permission_id', $duplicate->id)
                        ->get();

                    foreach ($links as $link) {
                        $exists = DB::table('user_permission')
                            ->where('user_id', $link->user_id)
                            ->where('permission_id', $canonical->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('user_permission')->insert([
                                'user_id' => $link->user_id,
                                'permission_id' => $canonical->id,
                            ]);
                        }
                    }

                    DB::table('user_permission')
                        ->where('permission_id', $duplicate->id)
                        ->delete();
                }

                DB::table('permissions')
                    ->where('id', $duplicate->id)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Harmonisation volontairement non destructrice.
    }

    private function canonicalName(?string $name): string
    {
        $name = $this->normalizeName($name);

        $directAliases = [
            'appercu_programm' => 'programmes_apercu',
            'apercu_programm' => 'programmes_apercu',
            'appercu_programme' => 'programmes_apercu',
            'apercu_programme' => 'programmes_apercu',
            'programme_apercu' => 'programmes_apercu',
            'programme_appercu' => 'programmes_apercu',
            'programm_apercu' => 'programmes_apercu',
            'program_apercu' => 'programmes_apercu',
        ];

        if (isset($directAliases[$name])) {
            return $directAliases[$name];
        }

        $parts = explode('_', $name, 2);
        if (count($parts) !== 2) {
            return $name;
        }

        return $this->canonicalModuleName($parts[0]) . '_' . $this->canonicalActionName($parts[1]);
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = str_replace([' ', '-'], '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    }

    private function canonicalModuleName(string $module): string
    {
        $module = $this->normalizeName($module);

        return [
            'inscription' => 'inscriptions',
            'planification' => 'planifications',
            'programme' => 'programmes',
            'programm' => 'programmes',
            'program' => 'programmes',
            'profile' => 'profiles',
        ][$module] ?? $module;
    }

    private function canonicalActionName(string $action): string
    {
        $action = $this->normalizeName($action);

        return [
            'appercu' => 'apercu',
            'aperçu' => 'apercu',
            'création' => 'creation',
            'modifier' => 'modification',
            'supression' => 'supprimer',
            'suppression' => 'supprimer',
        ][$action] ?? $action;
    }
};
