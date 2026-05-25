<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ConductNoteService
{
    public const BASE_NOTE = 18.0;
    public const MIN_NOTE = 6.0;

    public function penaltyForStudent(int $studentId, int $classId, int $yearId, int $trimesterId, int $schoolId, ?string $untilDate = null, ?int $untilCallId = null): float
    {
        $query = DB::table('controle_eleve')
            ->join('controle', 'controle.id_controle', '=', 'controle_eleve.id_controle')
            ->where('controle_eleve.id_eleve', $studentId)
            ->where('controle_eleve.id_classe', $classId)
            ->where('controle_eleve.id_annee_scolaire', $yearId)
            ->where('controle_eleve.id_trimestre', $trimesterId)
            ->where('controle_eleve.id_ecole', $schoolId);

        if ($untilDate !== null && $untilCallId !== null) {
            $query->where(function ($inner) use ($untilDate, $untilCallId) {
                $inner->where('controle_eleve.date', '<', $untilDate)
                    ->orWhere(function ($sameDate) use ($untilDate, $untilCallId) {
                        $sameDate->where('controle_eleve.date', $untilDate)
                            ->where('controle_eleve.id_controle_eleve', '<=', $untilCallId);
                    });
            });
        }

        return (float) $query->sum(DB::raw('ABS(COALESCE(controle.penalite_conduite, 0))'));
    }

    public function noteFromPenalty(float $penalty): float
    {
        return max(self::MIN_NOTE, min(self::BASE_NOTE, self::BASE_NOTE - $penalty));
    }

    public function noteForStudent(int $studentId, int $classId, int $yearId, int $trimesterId, int $schoolId): float
    {
        return $this->noteFromPenalty($this->penaltyForStudent($studentId, $classId, $yearId, $trimesterId, $schoolId));
    }

    public function syncStudent(int $studentId, int $classId, int $yearId, int $trimesterId, int $schoolId): float
    {
        $note = $this->noteForStudent($studentId, $classId, $yearId, $trimesterId, $schoolId);

        DB::table('conduite')->updateOrInsert(
            [
                'id_annee_scolaire' => $yearId,
                'id_classe' => $classId,
                'id_trimestre' => $trimesterId,
                'id_eleve' => $studentId,
            ],
            ['note_conduite' => $note]
        );

        return $note;
    }

    public function syncClass(int $classId, int $yearId, int $trimesterId, int $schoolId): void
    {
        $studentIds = DB::table('controle_eleve')
            ->where('id_classe', $classId)
            ->where('id_annee_scolaire', $yearId)
            ->where('id_trimestre', $trimesterId)
            ->where('id_ecole', $schoolId)
            ->distinct()
            ->pluck('id_eleve');

        foreach ($studentIds as $studentId) {
            $this->syncStudent((int) $studentId, $classId, $yearId, $trimesterId, $schoolId);
        }
    }
}
