<?php

use App\Models\Permission;
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

        $catalogNames = Permission::catalogNames();

        foreach ($catalogNames as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        $permissions = DB::table('permissions')->select('id', 'name')->orderBy('id')->get();

        foreach ($permissions->groupBy(fn ($permission) => Permission::canonicalName($permission->name)) as $canonicalName => $items) {
            if (!in_array($canonicalName, $catalogNames, true)) {
                $this->deletePermissions($items->pluck('id')->all());
                continue;
            }

            $canonical = DB::table('permissions')->where('name', $canonicalName)->orderBy('id')->first();

            if (!$canonical) {
                $canonical = $items->sortBy('id')->first();
                DB::table('permissions')->where('id', $canonical->id)->update(['name' => $canonicalName]);
            }

            foreach ($items as $duplicate) {
                if ((int) $duplicate->id === (int) $canonical->id) {
                    continue;
                }

                $this->moveUserPermissionLinks((int) $duplicate->id, (int) $canonical->id);
                $this->deletePermissions([(int) $duplicate->id]);
            }
        }

        $freshPermissions = DB::table('permissions')->select('id', 'name')->orderBy('id')->get();
        foreach ($freshPermissions as $permission) {
            if (!in_array(Permission::canonicalName($permission->name), $catalogNames, true)) {
                $this->deletePermissions([(int) $permission->id]);
            }
        }
    }

    public function down(): void
    {
        // Catalogue intentionally not rolled back.
    }

    private function moveUserPermissionLinks(int $fromPermissionId, int $toPermissionId): void
    {
        if (!Schema::hasTable('user_permission')) {
            return;
        }

        $links = DB::table('user_permission')
            ->where('permission_id', $fromPermissionId)
            ->get();

        foreach ($links as $link) {
            $exists = DB::table('user_permission')
                ->where('user_id', $link->user_id)
                ->where('permission_id', $toPermissionId)
                ->exists();

            if (!$exists) {
                DB::table('user_permission')->insert([
                    'user_id' => $link->user_id,
                    'permission_id' => $toPermissionId,
                ]);
            }
        }
    }

    private function deletePermissions(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        if (Schema::hasTable('user_permission')) {
            DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
