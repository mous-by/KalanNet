<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\Paiement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EleveController extends Controller
{
    public function index(Request $request)
    {
        $idEcole = session('idEcole');
        
        $classes = Classe::where('idEcole', $idEcole)->get();
        $annees = AnneeScolaire::all();

        $showList = $request->filled('id_classe') && $request->filled('id_annee');
        $eleves = collect();

        if (!$showList) {
            return view('eleves.index', compact('eleves', 'classes', 'annees', 'showList'));
        }

        $query = Eleve::where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->with('classe')
            ->where('id_classe', $request->id_classe)
            ->where('id_annee', $request->id_annee);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        $eleves = $query->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();

        return view('eleves.index', compact('eleves', 'classes', 'annees', 'showList'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $query = Eleve::with(['classe', 'ecole', 'parents', 'plansPaiement.echeances'])
            ->where('id_ecole', session('idEcole'));

        if ($user?->droit === 'parent') {
            $parentId = $user->id_parent ?: $user->parent?->id_parent;
            if (!$parentId) {
                abort(403, 'Aucun profil parent n’est lié à ce compte.');
            }
            $query->whereHas('parents', fn ($parentQuery) => $parentQuery->where('parents.id_parent', $parentId));
        } elseif (!$this->canOpenStudentDossiers($user)) {
            abort(403, 'Permission insuffisante.');
        }

        $eleve = $query->findOrFail($id);
        $annee = AnneeScolaire::where('id_anneeScolaire', $eleve->id_annee)->first();
        $paiements = Paiement::where('idEcole', session('idEcole'))
            ->where('id_eleve', $eleve->id_eleve)
            ->latest('date_paiement')
            ->get();
        $paiementsRecents = $paiements->take(8);
        $paymentSummary = $this->studentPaymentSummary($eleve, $paiements);
        $echeancesResume = $this->studentEcheancesResume($eleve, $paiements);
        $evaluationsRecentes = $this->studentRecentEvaluations($eleve);
        $moyennes = $this->studentMoyennes($eleve);
        $transferts = $this->studentTransfers($eleve);
        $dossierAlerts = $this->studentDossierAlerts($eleve, $paymentSummary, $echeancesResume);

        return view('eleves.show', compact(
            'eleve',
            'annee',
            'paiementsRecents',
            'paymentSummary',
            'echeancesResume',
            'evaluationsRecentes',
            'moyennes',
            'transferts',
            'dossierAlerts'
        ));
    }

    public function dossiers(Request $request)
    {
        $user = Auth::user();
        if ($user?->droit === 'parent') {
            return redirect()->route('dashboard');
        }
        if (!$this->canOpenStudentDossiers($user)) {
            abort(403, 'Permission insuffisante.');
        }

        $idEcole = session('idEcole');
        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $status = $request->get('status', 'actifs');
        $showList = $request->filled('id_classe') && $request->filled('id_annee');
        $eleves = collect();

        if (!$showList) {
            return view('eleves.dossiers', compact('eleves', 'classes', 'annees', 'status', 'showList'));
        }

        $query = Eleve::with(['classe', 'parents'])
            ->where('id_ecole', $idEcole);

        if ($status === 'transferes') {
            $query->where('etat_dossier', 1);
        } elseif ($status === 'retires') {
            $query->where('etat_dossier', 2);
        } else {
            $query->where('etat_dossier', 0);
        }

        if ($request->filled('id_classe')) {
            $query->where('id_classe', $request->id_classe);
        }

        if ($request->filled('id_annee')) {
            $query->where('id_annee', $request->id_annee);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        $eleves = $query->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->paginate(20)
            ->withQueryString();

        return view('eleves.dossiers', compact('eleves', 'classes', 'annees', 'status', 'showList'));
    }

    public function edit($id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->findOrFail($id);
        $classes = Classe::where('idEcole', session('idEcole'))->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();

        return view('eleves.form', compact('eleve', 'classes', 'annees'));
    }

    public function update(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->findOrFail($id);
        $data = $request->validate([
            'prenom_eleve' => 'required|string|max:255',
            'nom_eleve' => 'required|string|max:255',
            'matricule' => 'nullable|string|max:50',
            'genre_eleve' => 'required|string|in:Masculin,Féminin',
            'date_naissance' => 'nullable|date',
            'lieu_naiss' => 'nullable|string|max:255',
            'adresse_eleve' => 'nullable|string|max:255',
            'cas_social' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:255',
            'statut_paiement' => 'nullable|string|in:normal,subventionne,boursier,gratuit',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'date_inscription' => 'nullable|date',
        ]);

        Classe::where('idEcole', session('idEcole'))->findOrFail($data['id_classe']);

        $eleve->update([
            'prenom_eleve' => $data['prenom_eleve'],
            'nom_eleve' => $data['nom_eleve'],
            'matricule' => $data['matricule'] ?: $eleve->matricule,
            'genre_eleve' => $data['genre_eleve'],
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naiss' => $data['lieu_naiss'] ?: 'Non renseigné',
            'adresse_eleve' => $data['adresse_eleve'] ?? null,
            'cas_social' => $data['cas_social'] ?: 'normal',
            'mode_paiement' => $data['mode_paiement'] ?? null,
            'statut_paiement' => $data['statut_paiement'] ?? 'normal',
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
            'date_inscription' => $data['date_inscription'] ?? $eleve->date_inscription,
        ]);

        return redirect()->route('eleves.index')->with('success', 'Élève modifié avec succès.');
    }

    public function destroy($id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->findOrFail($id);
        $eleve->etat_dossier = 2;
        $eleve->save();

        return back()->with('success', 'Élève retiré de la liste active avec succès.');
    }

    public function transfer(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->where('etat_dossier', 0)->findOrFail($id);
        $data = $request->validate([
            'motif' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'travail' => 'nullable|string|max:255',
            'conduite' => 'required|string|max:255',
        ]);

        if (!Schema::hasTable('transfert')) {
            return back()->with('error', 'La table des transferts n’est pas disponible.');
        }

        $transferId = DB::transaction(function () use ($eleve, $data) {
            $transferId = DB::table('transfert')->insertGetId([
                'id_eleve' => $eleve->id_eleve,
                'id_ecole' => session('idEcole'),
                'motif' => $data['motif'],
                'destination' => $data['destination'],
                'travail' => $data['travail'] ?? null,
                'conduite' => $data['conduite'],
                ...$this->transferOptionalColumns(['date_transfert' => now()]),
            ]);

            $eleve->etat_dossier = 1;
            $eleve->save();

            return $transferId;
        });

        return redirect()->route('eleves.transfer.fiche', $transferId);
    }

    public function reintegrate(Request $request, $id)
    {
        $eleve = Eleve::where('id_ecole', session('idEcole'))->where('etat_dossier', 1)->findOrFail($id);
        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'motif_retour' => 'nullable|string|max:255',
        ]);

        Classe::where('idEcole', session('idEcole'))->findOrFail($data['id_classe']);

        DB::transaction(function () use ($eleve, $data) {
            $eleve->update([
                'etat_dossier' => 0,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
            ]);

            if (Schema::hasTable('transfert')) {
                $latestTransfer = DB::table('transfert')
                    ->where('id_eleve', $eleve->id_eleve)
                    ->where('id_ecole', session('idEcole'))
                    ->when(Schema::hasColumn('transfert', 'date_retour'), fn ($query) => $query->whereNull('date_retour'))
                    ->orderByDesc('id_transfert')
                    ->first();

                if ($latestTransfer) {
                    $returnColumns = $this->transferOptionalColumns([
                        'date_retour' => now(),
                        'motif_retour' => $data['motif_retour'] ?: 'Réintégration',
                        'retour_effectue_par' => Auth::id(),
                    ]);

                    if (!empty($returnColumns)) {
                        DB::table('transfert')
                            ->where('id_transfert', $latestTransfer->id_transfert)
                            ->update($returnColumns);
                    }
                }
            }
        });

        return redirect()
            ->route('eleves.dossiers', ['status' => 'actifs', 'id_classe' => $data['id_classe'], 'id_annee' => $data['id_annee']])
            ->with('success', 'Élève réintégré dans la liste active.');
    }

    public function transferCertificate($id)
    {
        if (!Schema::hasTable('transfert')) {
            abort(404);
        }

        $transfer = DB::table('transfert')
            ->where('id_transfert', $id)
            ->where('id_ecole', session('idEcole'))
            ->first();

        if (!$transfer) {
            abort(404);
        }

        $eleve = Eleve::with(['classe', 'ecole', 'parents'])
            ->where('id_ecole', session('idEcole'))
            ->findOrFail($transfer->id_eleve);
        $annee = AnneeScolaire::where('id_anneeScolaire', $eleve->id_annee)->first();
        $ecole = Ecole::withoutGlobalScopes()->find(session('idEcole'));

        $pdf = Pdf::loadView('pdf.eleve_transfert', compact('transfer', 'eleve', 'annee', 'ecole'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Fiche_transfert_' . ($eleve->matricule ?: $eleve->id_eleve) . '.pdf');
    }

    public function cartes(Request $request)
    {
        $idEcole = session('idEcole');
        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $showList = $request->filled('id_classe') && $request->filled('id_annee');
        $eleves = collect();

        if ($showList) {
            $eleves = $this->filteredEleves($request)->get();
        }

        return view('eleves.cartes', compact('classes', 'annees', 'eleves', 'showList'));
    }

    public function downloadCartesPdf(Request $request)
    {
        [$eleves, $classe, $annee, $ecole] = $this->listExportData($request);
        $config = $this->cardPrintConfig($request);
        $schoolName = $this->schoolCardName($ecole, $classe);
        $schoolLogoPath = $this->schoolLogoPath($ecole);
        $adminPhone = $this->schoolAdminPhone($ecole);
        $qrCodes = $this->studentQrCodes($eleves, $config, $classe, $annee, $schoolName, $adminPhone);

        $pdf = Pdf::loadView('pdf.cartes_scolaires', compact('eleves', 'classe', 'annee', 'ecole', 'config', 'qrCodes', 'schoolName', 'schoolLogoPath', 'adminPhone'))->setPaper('a4', 'portrait');

        return $pdf->download('Cartes_scolaires_' . str_replace(' ', '_', $classe->nom_classe) . '.pdf');
    }

    public function downloadListPdf(Request $request)
    {
        [$eleves, $classe, $annee, $ecole] = $this->listExportData($request);
        $pdf = Pdf::loadView('pdf.eleves_liste', compact('eleves', 'classe', 'annee', 'ecole'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Liste_eleves_' . str_replace(' ', '_', $classe->nom_classe) . '.pdf');
    }

    public function downloadListExcel(Request $request)
    {
        [$eleves, $classe, $annee, $ecole] = $this->listExportData($request);

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            return response()->streamDownload(function () use ($eleves) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['N°', 'Matricule', 'Prénom', 'Nom', 'Genre', 'Date de naissance', 'Lieu de naissance']);
                foreach ($eleves as $index => $eleve) {
                    fputcsv($out, $this->excelRow($eleve, $index));
                }
                fclose($out);
            }, 'liste_eleves.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liste eleves');
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $ecole?->nomEcole ?? 'Établissement scolaire');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Liste des élèves inscrits - ' . $classe->nom_classe . ' - ' . $annee->annee);
        $sheet->fromArray(['N°', 'Matricule', 'Prénom', 'Nom', 'Genre', 'Date de naissance', 'Lieu de naissance'], null, 'A4');

        $line = 5;
        foreach ($eleves as $index => $eleve) {
            $sheet->fromArray($this->excelRow($eleve, $index), null, 'A' . $line);
            $line++;
        }

        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G' . max(4, $line - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, 'Liste_eleves_' . str_replace(' ', '_', $classe->nom_classe) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function listExportData(Request $request): array
    {
        $idEcole = session('idEcole');
        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'search' => 'nullable|string|max:255',
            'selected_eleves' => 'nullable|string',
        ]);

        $classe = Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        $annee = AnneeScolaire::findOrFail($data['id_annee']);
        $selectedIds = collect(explode(',', $data['selected_eleves'] ?? ''))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = Eleve::with(['classe', 'parents'])
            ->where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->where('id_classe', $data['id_classe'])
            ->where('id_annee', $data['id_annee']);

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id_eleve', $selectedIds);
        } elseif (!empty($data['search'])) {
            $search = $data['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        $eleves = $query->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();

        if ($eleves->isEmpty()) {
            throw ValidationException::withMessages([
                'eleves' => 'Aucun élève disponible pour générer cette exportation.',
            ]);
        }

        $ecole = Ecole::withoutGlobalScopes()->find($idEcole);

        return [$eleves, $classe, $annee, $ecole];
    }

    private function filteredEleves(Request $request)
    {
        $query = Eleve::with(['classe', 'parents'])
            ->where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->where('id_classe', $request->id_classe)
            ->where('id_annee', $request->id_annee);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('prenom_eleve')->orderBy('nom_eleve');
    }

    private function cardPrintConfig(Request $request): array
    {
        $data = $request->validate([
            'template' => 'nullable|string|in:institutionnel,moderne,vertical,alliance_pro,horizon,compact',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'card_title' => 'nullable|string|max:80',
            'signature_label' => 'nullable|string|max:80',
            'signature_image' => 'nullable|string|max:500000|regex:/^data:image\/png;base64,/',
            'show_mali_flag' => 'nullable|boolean',
            'mali_flag_style' => 'nullable|string|in:corner,watermark',
            'show_school_logo' => 'nullable|boolean',
            'show_qr' => 'nullable|boolean',
        ]);

        return [
            'template' => $data['template'] ?? 'institutionnel',
            'primary_color' => $data['primary_color'] ?? '#0f766e',
            'secondary_color' => $data['secondary_color'] ?? '#1d4ed8',
            'card_title' => $data['card_title'] ?? 'CARTE D’IDENTITÉ SCOLAIRE',
            'signature_label' => $data['signature_label'] ?? 'Directeur',
            'signature_image' => $data['signature_image'] ?? null,
            'show_mali_flag' => (bool) ($data['show_mali_flag'] ?? true),
            'mali_flag_style' => $data['mali_flag_style'] ?? 'corner',
            'show_school_logo' => (bool) ($data['show_school_logo'] ?? true),
            'show_qr' => (bool) ($data['show_qr'] ?? true),
        ];
    }

    private function studentQrCodes($eleves, array $config, Classe $classe, AnneeScolaire $annee, string $schoolName, ?string $adminPhone): array
    {
        if (!$config['show_qr']) {
            return [];
        }

        $builder = new Builder(writer: new PngWriter());
        $codes = [];

        foreach ($eleves as $eleve) {
            $payload = implode("\n", array_filter([
                'CARTE SCOLAIRE',
                'Ecole: ' . $schoolName,
                $adminPhone ? 'Tel: ' . $adminPhone : null,
                'Annee: ' . $annee->annee,
                'Classe: ' . $classe->nom_classe,
                'Eleve: ' . trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'Matricule: ' . ($eleve->matricule ?: 'Non renseigne'),
                'Sexe: ' . ($eleve->genre_eleve ?: 'Non renseigne'),
                'Naissance: ' . ($eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseignee'),
                $eleve->lieu_naiss ? 'Lieu: ' . $eleve->lieu_naiss : null,
            ]));
            $codes[$eleve->id_eleve] = $builder->build(
                data: $payload,
                size: 96,
                margin: 2
            )->getDataUri();
        }

        return $codes;
    }

    private function schoolCardName(?Ecole $ecole, Classe $classe): string
    {
        if (!$ecole) {
            return 'Établissement scolaire';
        }

        $ordre = strtolower((string) $classe->ordreEnseignement);
        if (!empty($ecole->nomComplexe)) {
            if (str_contains($ordre, 'fondamentale') && !empty($ecole->nomFondamental)) {
                return $ecole->nomComplexe . ' - École fondamentale : ' . $ecole->nomFondamental;
            }
            if (str_contains($ordre, 'secondaire') && !empty($ecole->nomLycee)) {
                return $ecole->nomComplexe . ' - Lycée : ' . $ecole->nomLycee;
            }
            if (str_contains($ordre, 'technique') && !empty($ecole->nomProfessionnel)) {
                return $ecole->nomComplexe . ' - École professionnelle : ' . $ecole->nomProfessionnel;
            }
            return $ecole->nomComplexe;
        }

        return $ecole->nomEcole ?: 'Établissement scolaire';
    }

    private function schoolLogoPath(?Ecole $ecole): ?string
    {
        if (!$ecole || empty($ecole->logoEcole)) {
            return null;
        }

        $path = public_path(ltrim($ecole->logoEcole, '/'));

        return file_exists($path) ? $path : null;
    }

    private function studentPaymentSummary(Eleve $eleve, $paiements): array
    {
        $plan = $eleve->plansPaiement->sortByDesc('id')->first();
        $legacyPlanifications = $this->studentLegacyPlanifications($eleve);
        $legacyMontant = $legacyPlanifications->sum(fn ($item) => (float) $item->montant_planification);
        $montantFinal = (float) ($plan?->montant_final ?? 0);
        if ($montantFinal <= 0 && $legacyMontant > 0) {
            $montantFinal = $legacyMontant;
        }
        $montantTotal = (float) ($plan?->montant_total ?? 0);
        if ($montantTotal <= 0 && $legacyMontant > 0) {
            $montantTotal = $legacyMontant;
        }
        $reduction = (float) ($plan?->reduction ?? 0);
        $montantPaye = $paiements
            ->filter(fn ($paiement) => ($paiement->statut ?? '') !== 'annule')
            ->sum(fn ($paiement) => (float) ($paiement->montant_paye ?? $paiement->montant ?? 0));
        $reste = max(0, $montantFinal - $montantPaye);
        $progress = $montantFinal > 0 ? min(100, round(($montantPaye / $montantFinal) * 100)) : 0;

        return [
            'plan' => $plan,
            'legacy_planifications' => $legacyPlanifications,
            'montant_total' => $montantTotal,
            'reduction' => $reduction,
            'montant_final' => $montantFinal,
            'montant_paye' => $montantPaye,
            'reste' => $reste,
            'progress' => $progress,
            'statut' => $reste <= 0 && $montantFinal > 0 ? 'Soldé' : ($montantPaye > 0 ? 'En cours' : 'Non démarré'),
        ];
    }

    private function studentEcheancesResume(Eleve $eleve, $paiements): array
    {
        $plan = $eleve->plansPaiement->sortByDesc('id')->first();
        if (!$plan) {
            return $this->studentLegacyPlanifications($eleve)
                ->map(function ($planification) use ($paiements) {
                    $paid = $paiements
                        ->where('id_planification', $planification->id_planification)
                        ->filter(fn ($paiement) => ($paiement->statut ?? '') !== 'annule')
                        ->sum(fn ($paiement) => (float) ($paiement->montant_paye ?? $paiement->montant ?? 0));
                    $remaining = max(0, (float) $planification->montant_planification - $paid);
                    $dateLimite = $planification->date_fin ? \Illuminate\Support\Carbon::parse($planification->date_fin) : null;

                    return [
                        'libelle' => $planification->motif,
                        'montant_prevu' => (float) $planification->montant_planification,
                        'paye' => $paid,
                        'reste' => $remaining,
                        'date_limite' => $dateLimite,
                        'statut' => $remaining <= 0 ? 'paye' : ($dateLimite && $dateLimite->isPast() ? 'retard' : 'attente'),
                    ];
                })
                ->values()
                ->all();
        }

        return $plan->echeances
            ->sortBy('date_limite')
            ->map(function ($echeance) use ($paiements) {
                $paid = $paiements
                    ->where('echeance_id', $echeance->id)
                    ->filter(fn ($paiement) => ($paiement->statut ?? '') !== 'annule')
                    ->sum(fn ($paiement) => (float) ($paiement->montant_paye ?? $paiement->montant ?? 0));
                $remaining = max(0, (float) $echeance->montant_prevu - $paid);

                return [
                    'libelle' => $echeance->libelle,
                    'montant_prevu' => (float) $echeance->montant_prevu,
                    'paye' => $paid,
                    'reste' => $remaining,
                    'date_limite' => $echeance->date_limite,
                    'statut' => $remaining <= 0 ? 'paye' : ($echeance->date_limite && $echeance->date_limite->isPast() ? 'retard' : ($echeance->statut ?: 'attente')),
                ];
            })
            ->values()
            ->all();
    }

    private function studentRecentEvaluations(Eleve $eleve)
    {
        if (!Schema::hasTable('ligne_evaluation')) {
            return collect();
        }

        $query = DB::table('ligne_evaluation as le')
            ->where('le.id_eleve', $eleve->id_eleve)
            ->leftJoin('matiere as m', 'le.id_matiere', '=', 'm.id_matiere')
            ->leftJoin('note as n', 'le.id_note', '=', 'n.id_note')
            ->leftJoin('trimestre as t', 'le.id_trimestre', '=', 't.id_trimestre')
            ->select([
                'le.note',
                'le.mois',
                'm.nom_matiere',
                'n.typeNote',
                'n.codeNote',
                't.nom_trimestre',
            ]);

        if (Schema::hasColumn('ligne_evaluation', 'id_annee_scolaire')) {
            $query->where('le.id_annee_scolaire', $eleve->id_annee);
        }

        return $query->orderByDesc('le.id_ligneEvaluation')
            ->limit(8)
            ->get();
    }

    private function studentMoyennes(Eleve $eleve)
    {
        if (!Schema::hasTable('moyenne_eleve')) {
            return collect();
        }

        return DB::table('moyenne_eleve as me')
            ->leftJoin('trimestre as t', 'me.id_trimestre', '=', 't.id_trimestre')
            ->where('me.id_eleve', $eleve->id_eleve)
            ->when(Schema::hasColumn('moyenne_eleve', 'id_anneeScolaire'), fn ($query) => $query->where('me.id_anneeScolaire', $eleve->id_annee))
            ->select(['me.moyenne', 'me.rang', 'me.mois', 'me.valide', 't.nom_trimestre'])
            ->orderByRaw('COALESCE(me.id_trimestre, 0) desc')
            ->limit(6)
            ->get();
    }

    private function studentTransfers(Eleve $eleve)
    {
        if (!Schema::hasTable('transfert')) {
            return collect();
        }

        return DB::table('transfert')
            ->where('id_eleve', $eleve->id_eleve)
            ->where('id_ecole', session('idEcole'))
            ->orderByDesc('id_transfert')
            ->get();
    }

    private function transferOptionalColumns(array $values): array
    {
        if (!Schema::hasTable('transfert')) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value, $column) => Schema::hasColumn('transfert', $column))
            ->all();
    }

    private function studentDossierAlerts(Eleve $eleve, array $paymentSummary, array $echeancesResume): array
    {
        $alerts = [];

        if ($eleve->parents->isEmpty()) {
            $alerts[] = ['type' => 'warning', 'text' => 'Aucun parent n’est rattaché à ce dossier.'];
        }

        if (empty($eleve->matricule)) {
            $alerts[] = ['type' => 'warning', 'text' => 'Le matricule n’est pas renseigné.'];
        }

        if (!$paymentSummary['plan'] && $paymentSummary['legacy_planifications']->isEmpty()) {
            $alerts[] = ['type' => 'info', 'text' => 'Aucun plan de paiement n’est encore attaché à cet élève.'];
        }

        $lateCount = collect($echeancesResume)->where('statut', 'retard')->count();
        if ($lateCount > 0) {
            $alerts[] = ['type' => 'danger', 'text' => $lateCount . ' échéance(s) de paiement sont en retard.'];
        }

        if ((int) $eleve->etat_dossier === 1) {
            $alerts[] = ['type' => 'info', 'text' => 'Ce dossier est marqué comme transféré.'];
        } elseif ((int) $eleve->etat_dossier === 2) {
            $alerts[] = ['type' => 'secondary', 'text' => 'Ce dossier est retiré de la liste active.'];
        }

        return $alerts;
    }

    private function studentLegacyPlanifications(Eleve $eleve)
    {
        if (!Schema::hasTable('ligne_inscription') || !Schema::hasTable('planification')) {
            return collect();
        }

        return DB::table('ligne_inscription as li')
            ->join('planification as p', 'li.id_planification', '=', 'p.id_planification')
            ->where('li.id_eleve', $eleve->id_eleve)
            ->where('li.id_annee', $eleve->id_annee)
            ->select([
                'p.id_planification',
                'p.motif',
                'p.date_debut',
                'p.date_fin',
                'p.montant_planification',
            ])
            ->get();
    }

    private function canOpenStudentDossiers($user): bool
    {
        return $user
            && ($user->droit === 'SupAdmin'
                || $user->userHasAnyPermission(['eleves_dossier', 'dossiers_eleves_apercu']));
    }

    private function schoolAdminPhone(?Ecole $ecole): ?string
    {
        if (!$ecole) {
            return null;
        }

        $adminPhone = User::withoutGlobalScopes()
            ->where('idEcole', $ecole->idEcole)
            ->where('droit', 'Admin')
            ->whereNotNull('telephone')
            ->value('telephone');

        return $adminPhone ?: ($ecole->telephone ?: null);
    }

    private function excelRow(Eleve $eleve, int $index): array
    {
        return [
            $index + 1,
            $eleve->matricule,
            $eleve->prenom_eleve,
            $eleve->nom_eleve,
            $eleve->genre_eleve,
            $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : '',
            $eleve->lieu_naiss,
        ];
    }

}
