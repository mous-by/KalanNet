<?php

namespace App\Traits;

use App\Models\Ecole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToSchool
{
    /**
     * Boot the BelongsToSchool trait to register a global Eloquent Query Scope.
     */
    public static function bootBelongsToSchool()
    {
        static::addGlobalScope('school', function (Builder $builder) {
            $model = new static;
            $table = $model->getTable();

            // Prevent infinite recursion by not applying the scope when resolving the authenticated user
            if (auth()->hasUser()) {
                $user = auth()->user();
            } elseif ($table !== 'utilisateurs' && Auth::check()) {
                $user = Auth::user();
            } else {
                return;
            }

            // 1. Special handling for the 'ecole' table itself (primary key is 'idEcole')
            if ($table === 'ecole') {
                if ($user->droit === 'SupAdmin') {
                    return;
                }
                if ($user->droit === 'DAE') {
                    $builder->where('id_academie', $user->id_academie);
                    return;
                }
                if ($user->droit === 'DCAP') {
                    $builder->where('id_cap', $user->id_cap);
                    return;
                }
                $builder->where('idEcole', session('idEcole') ?: $user->idEcole);
                return;
            }

            // 2. Identify the correct column for schools in other tables
            $column = in_array($table, ['classe', 'parents', 'utilisateurs', 'paiement']) ? 'idEcole' : 'id_ecole';

            // If a school is selected in session, scope everything to that school
            $selectedSchool = session('idEcole');
            if ($selectedSchool) {
                if ($table === 'ecole') {
                    $builder->where('idEcole', $selectedSchool);
                    return;
                }

                $builder->where(function ($q) use ($column, $selectedSchool, $table) {
                    $q->where($column, $selectedSchool);
                    if (in_array($table, ['matiere', 'anneescolaire', 'trimestre'])) {
                        $q->orWhereNull($column);
                    }
                });
                return;
            }

            // SupAdmin sees everything
            if ($user->droit === 'SupAdmin') {
                return;
            }

            // Academie supervisor (DAE) sees everything in their Academie
            if ($user->droit === 'DAE') {
                $builder->where(function ($q) use ($user, $column, $table) {
                    $q->whereIn($column, function ($query) use ($user) {
                        $query->select('idEcole')
                            ->from('ecole')
                            ->where('id_academie', $user->id_academie);
                    });
                    // Allow global/null items in specific lookup tables
                    if (in_array($table, ['matiere', 'anneescolaire', 'trimestre'])) {
                        $q->orWhereNull($column);
                    }
                });
                return;
            }

            // CAP supervisor (DCAP) sees everything in their CAP
            if ($user->droit === 'DCAP') {
                $builder->where(function ($q) use ($user, $column, $table) {
                    $q->whereIn($column, function ($query) use ($user) {
                        $query->select('idEcole')
                            ->from('ecole')
                            ->where('id_cap', $user->id_cap);
                    });
                    // Allow global/null items in specific lookup tables
                    if (in_array($table, ['matiere', 'anneescolaire', 'trimestre'])) {
                        $q->orWhereNull($column);
                    }
                });
                return;
            }

            // Regular school users (Admin, Gestionnaire, enseignant, parent)
            $idEcole = session('idEcole');
            if ($idEcole) {
                $builder->where(function ($q) use ($column, $idEcole, $table) {
                    $q->where($column, $idEcole);
                    if (in_array($table, ['matiere', 'anneescolaire', 'trimestre'])) {
                        $q->orWhereNull($column);
                    }
                });
            } else {
                // Fallback if no school context is selected yet, check user's assigned school
                if ($user->idEcole) {
                    $builder->where(function ($q) use ($column, $user, $table) {
                        $q->where($column, $user->idEcole);
                        if (in_array($table, ['matiere', 'anneescolaire', 'trimestre'])) {
                            $q->orWhereNull($column);
                        }
                    });
                }
            }
        });
    }
}
