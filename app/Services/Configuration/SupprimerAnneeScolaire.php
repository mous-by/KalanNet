<?php

namespace App\Services\Configuration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SupprimerAnneeScolaire
{
    private const COLONNES_ANNEE = ['id_annee', 'id_anneeScolaire', 'id_annee_scolaire', 'annee_scolaire_id'];

    /** @return array<string, int> Nombre de lignes supprimées par table. */
    public function supprimer(int $anneeId): array
    {
        return DB::transaction(function () use ($anneeId) {
            $database = DB::connection()->getDatabaseName();
            $tables = collect(Schema::getTables())
                ->filter(fn ($table) => ($table['schema'] ?? null) === $database)
                ->pluck('name')
                ->all();

            $primaryKeys = $this->primaryKeys($database);
            $foreignKeys = $this->foreignKeys($database);
            $columnsToSelect = $this->columnsToSelect($primaryKeys, $foreignKeys);
            $rowsByTable = [];
            $pendingByTable = [];

            $annee = DB::table('anneescolaire')->where('id_anneeScolaire', $anneeId)->first();
            if (!$annee) {
                return [];
            }
            $this->rememberRows('anneescolaire', [$annee], $primaryKeys, $rowsByTable, $pendingByTable);

            foreach ($tables as $table) {
                if ($table === 'anneescolaire') {
                    continue;
                }

                $referenceColumns = array_intersect(Schema::getColumnListing($table), self::COLONNES_ANNEE);
                if ($referenceColumns === []) {
                    continue;
                }

                $query = DB::table($table)->select($columnsToSelect[$table] ?? Schema::getColumnListing($table));
                $query->where(function ($references) use ($referenceColumns, $anneeId) {
                    foreach ($referenceColumns as $column) {
                        $references->orWhere($column, $anneeId);
                    }
                });

                $this->rememberRows($table, $query->get(), $primaryKeys, $rowsByTable, $pendingByTable);
            }

            $incomingForeignKeys = collect($foreignKeys)->groupBy('referenced_table');
            while ($pendingByTable !== []) {
                $current = $pendingByTable;
                $pendingByTable = [];

                foreach ($current as $parentTable => $parentRows) {
                    foreach ($incomingForeignKeys->get($parentTable, []) as $foreignKey) {
                        $tuples = collect($parentRows)
                            ->map(fn ($row) => array_map(fn ($column) => $row[$column] ?? null, $foreignKey['referenced_columns']))
                            ->filter(fn ($tuple) => !in_array(null, $tuple, true))
                            ->unique(fn ($tuple) => json_encode($tuple))
                            ->values();

                        foreach ($tuples->chunk(200) as $tupleChunk) {
                            $query = DB::table($foreignKey['table'])->select(
                                $columnsToSelect[$foreignKey['table']] ?? Schema::getColumnListing($foreignKey['table'])
                            );
                            $this->whereForeignKeyTuples($query, $foreignKey['columns'], $tupleChunk->all());
                            $this->rememberRows($foreignKey['table'], $query->get(), $primaryKeys, $rowsByTable, $pendingByTable);
                        }
                    }
                }
            }

            $counts = [];
            $connection = DB::connection();
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($rowsByTable as $table => $rows) {
                    if ($table === 'anneescolaire') {
                        continue;
                    }

                    $deleted = $this->deleteRows($table, $primaryKeys[$table], array_values($rows));
                    if ($deleted > 0) {
                        $counts[$table] = $deleted;
                    }
                }
            } finally {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }

            $counts['anneescolaire'] = DB::table('anneescolaire')
                ->where('id_anneeScolaire', $anneeId)
                ->delete();

            return $counts;
        });
    }

    private function primaryKeys(string $database): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND CONSTRAINT_NAME = \'PRIMARY\'
             ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$database]
        );

        $keys = [];
        foreach ($rows as $row) {
            $keys[$row->TABLE_NAME][] = $row->COLUMN_NAME;
        }

        return $keys;
    }

    private function foreignKeys(string $database): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, ORDINAL_POSITION,
                    REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION',
            [$database]
        );

        $keys = [];
        foreach ($rows as $row) {
            $key = $row->TABLE_NAME.'|'.$row->CONSTRAINT_NAME;
            $keys[$key] ??= [
                'table' => $row->TABLE_NAME,
                'referenced_table' => $row->REFERENCED_TABLE_NAME,
                'columns' => [],
                'referenced_columns' => [],
            ];
            $keys[$key]['columns'][] = $row->COLUMN_NAME;
            $keys[$key]['referenced_columns'][] = $row->REFERENCED_COLUMN_NAME;
        }

        $keys = array_values($keys);
        $tables = collect(Schema::getTables())
            ->filter(fn ($table) => ($table['schema'] ?? null) === $database)
            ->pluck('name')
            ->all();
        $primaryKeys = $this->primaryKeys($database);
        $columnsByTable = [];
        foreach ($tables as $table) {
            $columnsByTable[$table] = Schema::getColumnListing($table);
        }

        $aliases = [
            'paiement' => ['encaissement_id'],
            'encaissement' => ['paiement_id'],
        ];

        foreach ($primaryKeys as $parentTable => $parentColumns) {
            foreach ($tables as $childTable) {
                if ($childTable === $parentTable) {
                    continue;
                }

                foreach ($parentColumns as $parentColumn) {
                    $childColumn = collect($columnsByTable[$childTable])
                        ->first(fn ($column) => strtolower($column) === strtolower($parentColumn));
                    $candidateColumns = array_filter([
                        $childColumn,
                        ...($aliases[$parentTable] ?? []),
                    ]);

                    foreach ($candidateColumns as $candidateColumn) {
                        if (!in_array($candidateColumn, $columnsByTable[$childTable], true)) {
                            continue;
                        }

                        $key = $childTable.'|inferred|'.$parentTable.'|'.$candidateColumn.'|'.$parentColumn;
                        $exists = collect($keys)->contains(fn ($foreignKey) =>
                            $foreignKey['table'] === $childTable
                            && $foreignKey['referenced_table'] === $parentTable
                            && $foreignKey['columns'] === [$candidateColumn]
                            && $foreignKey['referenced_columns'] === [$parentColumn]
                        );
                        if (!$exists) {
                            $keys[$key] = [
                                'table' => $childTable,
                                'referenced_table' => $parentTable,
                                'columns' => [$candidateColumn],
                                'referenced_columns' => [$parentColumn],
                            ];
                        }
                    }
                }
            }
        }

        return array_values($keys);
    }

    private function columnsToSelect(array $primaryKeys, array $foreignKeys): array
    {
        $columns = $primaryKeys;
        foreach ($foreignKeys as $foreignKey) {
            $columns[$foreignKey['referenced_table']] = array_merge(
                $columns[$foreignKey['referenced_table']] ?? [],
                $foreignKey['referenced_columns']
            );
        }

        foreach ($columns as $table => $tableColumns) {
            $columns[$table] = array_values(array_unique($tableColumns));
        }

        return $columns;
    }

    private function rememberRows(string $table, iterable $rows, array $primaryKeys, array &$rowsByTable, array &$pendingByTable): void
    {
        $keys = $primaryKeys[$table] ?? [];
        if ($keys === []) {
            throw new RuntimeException("Impossible de supprimer en cascade : la table {$table} n'a pas de clé primaire.");
        }

        foreach ($rows as $row) {
            $values = (array) $row;
            $identity = array_map(fn ($key) => $values[$key] ?? null, $keys);
            if (in_array(null, $identity, true)) {
                throw new RuntimeException("Impossible d'identifier une ligne de {$table} à supprimer.");
            }

            $hash = json_encode($identity, JSON_THROW_ON_ERROR);
            if (isset($rowsByTable[$table][$hash])) {
                continue;
            }

            $rowsByTable[$table][$hash] = $values;
            $pendingByTable[$table][] = $values;
        }
    }

    private function whereForeignKeyTuples($query, array $columns, array $tuples): void
    {
        if (count($columns) === 1) {
            $query->whereIn($columns[0], array_map(fn ($tuple) => $tuple[0], $tuples));

            return;
        }

        $query->where(function ($matches) use ($columns, $tuples) {
            foreach ($tuples as $tuple) {
                $matches->orWhere(function ($match) use ($columns, $tuple) {
                    foreach ($columns as $index => $column) {
                        $match->where($column, $tuple[$index]);
                    }
                });
            }
        });
    }

    private function deleteRows(string $table, array $keys, array $rows): int
    {
        $deleted = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            $query = DB::table($table);
            if (count($keys) === 1) {
                $query->whereIn($keys[0], array_map(fn ($row) => $row[$keys[0]], $chunk));
            } else {
                $query->where(function ($matches) use ($keys, $chunk) {
                    foreach ($chunk as $row) {
                        $matches->orWhere(function ($match) use ($keys, $row) {
                            foreach ($keys as $key) {
                                $match->where($key, $row[$key]);
                            }
                        });
                    }
                });
            }
            $deleted += $query->delete();
        }

        return $deleted;
    }
}