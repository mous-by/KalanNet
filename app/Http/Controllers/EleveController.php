<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
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

        $eleves = $query->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
            ->get();

        return view('eleves.index', compact('eleves', 'classes', 'annees', 'showList'));
    }

    public function show($id)
    {
        $eleve = Eleve::with(['classe', 'ecole'])->where('id_ecole', session('idEcole'))->findOrFail($id);
        return view('eleves.show', compact('eleve'));
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

        DB::transaction(function () use ($eleve, $data) {
            DB::table('transfert')->insert([
                'id_eleve' => $eleve->id_eleve,
                'id_ecole' => session('idEcole'),
                'motif' => $data['motif'],
                'destination' => $data['destination'],
                'travail' => $data['travail'] ?? null,
                'conduite' => $data['conduite'],
            ]);

            $eleve->etat_dossier = 1;
            $eleve->save();
        });

        return back()->with('success', 'Transfert de l’élève enregistré avec succès.');
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

        $eleves = $query->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
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

        return $query->orderBy('nom_eleve')->orderBy('prenom_eleve');
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
