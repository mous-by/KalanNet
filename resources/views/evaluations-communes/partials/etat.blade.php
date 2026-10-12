{{-- Badge d'état d'une évaluation commune. --}}
@php($couleurs = ['Annulée' => 'bg-secondary', 'Sujet pas encore libéré' => 'bg-warning text-dark', 'Sujet disponible' => 'bg-info text-dark', 'En cours' => 'bg-primary', 'Terminée' => 'bg-success'])
<span class="badge {{ $couleurs[$evaluation->etat] ?? 'bg-light text-dark' }}">{{ $evaluation->etat }}</span>
