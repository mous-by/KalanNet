<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Models\ParentModel;
use App\Models\Planification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InscriptionController extends Controller
{
    public function index(Request $request)
    {
        $classes = Classe::where('idEcole', session('idEcole'))->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $parents = ParentModel::where('idEcole', session('idEcole'))->orderBy('nom_prenom_parent')->get();
        $planifications = Planification::orderBy('motif')->get();
        $eleves = Eleve::where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->with('classe')
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
            ->get();
        $activeTab = in_array($request->query('tab'), ['group', 'reinscription'], true) ? $request->query('tab') : 'individual';

        return view('pedagogie.inscriptions.index', compact('classes', 'annees', 'parents', 'planifications', 'eleves', 'activeTab'));
    }

    public function create()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'individual']);
    }

    public function createGroup()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'group']);
    }

    public function createReinscription()
    {
        return redirect()->route('inscriptions.index', ['tab' => 'reinscription']);
    }

    public function importGroup(Request $request)
    {
        $data = $request->validate([
            'fichier_excel' => 'required|file|mimes:xls,xlsx',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_planification' => 'required|exists:planification,id_planification',
            'date_inscription' => 'nullable|date',
        ]);

        $idEcole = session('idEcole');
        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        Planification::where('id_classe', $data['id_classe'])
            ->where('id_annee', $data['id_annee'])
            ->findOrFail($data['id_planification']);

        if (!class_exists(IOFactory::class)) {
            return back()->with('error', 'La bibliothèque PhpSpreadsheet n’est pas disponible.');
        }

        $spreadsheet = IOFactory::load($request->file('fichier_excel')->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestDataRow();
        $createdCount = 0;

        DB::transaction(function () use ($worksheet, $highestRow, $data, $idEcole, &$createdCount) {
            $dateInscription = $data['date_inscription'] ?? now()->toDateString();

            for ($row = 2; $row <= $highestRow; $row++) {
                $prenom = trim((string) $worksheet->getCell('A' . $row)->getValue());
                $nom = trim((string) $worksheet->getCell('B' . $row)->getValue());
                if ($prenom === '' && $nom === '') {
                    continue;
                }
                if ($prenom === '' || $nom === '') {
                    continue;
                }

                $dateNaissance = $this->normalizeExcelDate($worksheet->getCell('C' . $row)->getValue());
                $lieuNaiss = trim((string) $worksheet->getCell('D' . $row)->getValue()) ?: 'Non renseigné';
                $adresse = trim((string) $worksheet->getCell('E' . $row)->getValue()) ?: null;
                $genre = ucfirst(strtolower(trim((string) $worksheet->getCell('F' . $row)->getValue())));
                if (!in_array($genre, ['Masculin', 'Féminin'], true)) {
                    $genre = 'Masculin';
                }
                $casSocial = trim((string) $worksheet->getCell('G' . $row)->getValue());
                $casSocialNormalized = strtolower($casSocial);
                if ($casSocialNormalized === 'dispensé' || $casSocialNormalized === 'dispenser') {
                    $casSocial = 'Dipenser';
                } elseif ($casSocialNormalized === 'malade') {
                    $casSocial = 'Malade';
                } else {
                    $casSocial = 'normal';
                }
                $matricule = trim((string) $worksheet->getCell('H' . $row)->getValue()) ?: null;

                $eleve = new Eleve();
                $eleve->prenom_eleve = $prenom;
                $eleve->nom_eleve = $nom;
                $eleve->date_naissance = $dateNaissance;
                $eleve->lieu_naiss = $lieuNaiss;
                $eleve->adresse_eleve = $adresse;
                $eleve->id_classe = $data['id_classe'];
                $eleve->id_annee = $data['id_annee'];
                $eleve->genre_eleve = $genre;
                $eleve->matricule = $matricule ?: $this->generateMatricule(['nom_eleve' => $nom, 'prenom_eleve' => $prenom]);
                $eleve->date_inscription = $dateInscription;
                $eleve->image = 'assets/images/avatars/avatar-1.png';
                $eleve->cas_social = $casSocial;
                $eleve->mode_paiement = null;
                $eleve->id_ecole = $idEcole;
                $eleve->etat_dossier = 0;
                $eleve->save();

                DB::table('ligne_inscription')->insert([
                    'id_eleve' => $eleve->id_eleve,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'id_planification' => $data['id_planification'],
                    'date_inscription' => $dateInscription,
                ]);

                $createdCount++;
            }

            if ($createdCount === 0) {
                throw new \Exception('Aucune ligne valide trouvée dans le fichier Excel.');
            }
        });

        return redirect()->route('inscriptions.index', ['tab' => 'group'])->with('success', "Import effectué : {$createdCount} élève(s) inscrits.");
    }

    public function downloadGroupTemplate()
    {
        if (!class_exists(IOFactory::class)) {
            return back()->with('error', 'La bibliothèque PhpSpreadsheet n’est pas disponible.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'prenom_eleve');
        $sheet->setCellValue('B1', 'nom_eleve');
        $sheet->setCellValue('C1', 'date_naissance');
        $sheet->setCellValue('D1', 'lieu_naissance');
        $sheet->setCellValue('E1', 'adresse_eleve');
        $sheet->setCellValue('F1', 'genre_eleve');
        $sheet->setCellValue('G1', 'cas_social');
        $sheet->setCellValue('H1', 'matricule');
        $sheet->setCellValue('A2', 'Issa');
        $sheet->setCellValue('B2', 'Diallo');
        $sheet->setCellValue('C2', '2009-04-22');
        $sheet->setCellValue('D2', 'Ségou');
        $sheet->setCellValue('E2', 'Banankabougou');
        $sheet->setCellValue('F2', 'Masculin');
        $sheet->setCellValue('G2', 'normal');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modele_inscription_groupe.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function normalizeExcelDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            return $normalized;
        }

        $timestamp = strtotime($normalized);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom_eleve' => 'required',
            'nom_eleve' => 'required',
            'id_classe' => 'required',
            'id_annee' => 'required',
            'genre_eleve' => 'required',
            'date_naissance' => 'nullable',
            'lieu_naiss' => 'nullable',
            'adresse_eleve' => 'nullable',
            'cas_social' => 'nullable',
            'mode_paiement' => 'nullable',
            'date_inscription' => 'nullable|date',
            'matricule' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120',
            'parent_id' => 'nullable|exists:parents,id_parent',
            'lien_parent' => 'nullable|string|max:100',
            'informer' => 'nullable|string|in:Oui,Non',
            'id_planification' => 'required|exists:planification,id_planification',
        ]);

        Planification::where('id_classe', $data['id_classe'])
            ->where('id_annee', $data['id_annee'])
            ->findOrFail($data['id_planification']);

        DB::transaction(function () use ($request, $data) {
            $eleve = new Eleve();
            $eleve->prenom_eleve = $data['prenom_eleve'];
            $eleve->nom_eleve = $data['nom_eleve'];
            $eleve->date_naissance = $data['date_naissance'] ?? null;
            $eleve->lieu_naiss = $data['lieu_naiss'] ?: 'Non renseigné';
            $eleve->adresse_eleve = $data['adresse_eleve'] ?? null;
            $eleve->id_classe = $data['id_classe'];
            $eleve->id_annee = $data['id_annee'];
            $eleve->genre_eleve = $data['genre_eleve'];
            $eleve->matricule = $data['matricule'] ?: $this->generateMatricule($data);
            $eleve->date_inscription = $data['date_inscription'] ?? now()->toDateString();
            $eleve->image = $this->storeImage($request);
            $eleve->cas_social = $data['cas_social'] ?: 'normal';
            $eleve->mode_paiement = $data['mode_paiement'] ?? null;
            $eleve->id_ecole = session('idEcole');
            $eleve->save();

            if (!empty($data['parent_id'])) {
                $eleve->parents()->syncWithoutDetaching([
                    $data['parent_id'] => [
                        'lien_parent' => $data['lien_parent'] ?? 'Parent',
                        'informer' => $data['informer'] ?? 'Non',
                    ],
                ]);
            }

            DB::table('ligne_inscription')->insert([
                'id_eleve' => $eleve->id_eleve,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
                'id_planification' => $data['id_planification'],
                'date_inscription' => $eleve->date_inscription,
            ]);
        });

        return redirect()->route('eleves.index')->with('success', 'Élève inscrit avec succès.');
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_planification' => 'required|exists:planification,id_planification',
            'date_inscription' => 'nullable|date',
            'eleves' => 'required|array|min:1',
            'eleves.*.prenom_eleve' => 'required|string|max:255',
            'eleves.*.nom_eleve' => 'required|string|max:255',
            'eleves.*.genre_eleve' => 'required|string',
            'eleves.*.date_naissance' => 'nullable|date',
            'eleves.*.lieu_naiss' => 'nullable|string|max:255',
            'eleves.*.matricule' => 'nullable|string|max:50',
        ]);

        $idEcole = session('idEcole');
        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        Planification::where('id_classe', $data['id_classe'])
            ->where('id_annee', $data['id_annee'])
            ->findOrFail($data['id_planification']);
        $dateInscription = $data['date_inscription'] ?? now()->toDateString();

        DB::transaction(function () use ($data, $dateInscription, $idEcole) {
            foreach ($data['eleves'] as $row) {
                $eleve = Eleve::create([
                    'prenom_eleve' => $row['prenom_eleve'],
                    'nom_eleve' => $row['nom_eleve'],
                    'date_naissance' => $row['date_naissance'] ?? null,
                    'lieu_naiss' => $row['lieu_naiss'] ?? 'Non renseigné',
                    'adresse_eleve' => null,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'genre_eleve' => $row['genre_eleve'],
                    'matricule' => $row['matricule'] ?: $this->generateMatricule($row),
                    'date_inscription' => $dateInscription,
                    'image' => 'assets/images/avatars/avatar-1.png',
                    'cas_social' => 'normal',
                    'mode_paiement' => null,
                    'id_ecole' => $idEcole,
                    'etat_dossier' => 0,
                ]);

                DB::table('ligne_inscription')->insert([
                    'id_eleve' => $eleve->id_eleve,
                    'id_classe' => $data['id_classe'],
                    'id_annee' => $data['id_annee'],
                    'id_planification' => $data['id_planification'],
                    'date_inscription' => $dateInscription,
                ]);
            }
        });

        return redirect()->route('eleves.index', ['id_classe' => $data['id_classe'], 'id_annee' => $data['id_annee']])
            ->with('success', 'Inscription par groupe enregistrée avec succès.');
    }

    public function storeReinscription(Request $request)
    {
        $data = $request->validate([
            'id_eleve' => 'required|exists:eleve,id_eleve',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'statut' => 'required|in:passant,redoublant,non défini',
            'date_reinscription' => 'nullable|date',
        ]);

        $idEcole = session('idEcole');
        $eleve = Eleve::where('id_ecole', $idEcole)
            ->where('etat_dossier', 0)
            ->findOrFail($data['id_eleve']);

        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        AnneeScolaire::findOrFail($data['id_annee']);

        $dateReinscription = $data['date_reinscription'] ?? now()->toDateString();

        if (DB::table('ligne_reinscription')
            ->where('id_eleve', $eleve->id_eleve)
            ->where('id_annee', $data['id_annee'])
            ->exists()) {
            return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
                ->with('error', 'Cet élève a déjà été réinscrit pour l’année scolaire choisie.');
        }

        DB::transaction(function () use ($eleve, $data, $dateReinscription) {
            $reinscriptionId = DB::table('reinscription')->insertGetId([
                'statut' => $data['statut'],
                'date_reinscription' => $dateReinscription,
                'enrolement' => 1,
                'moyenneGeneral' => null,
            ]);

            DB::table('ligne_reinscription')->insert([
                'id_eleve' => $eleve->id_eleve,
                'id_classe' => $data['id_classe'],
                'id_annee' => $data['id_annee'],
                'id_reinscription' => $reinscriptionId,
            ]);

            $eleve->id_classe = $data['id_classe'];
            $eleve->id_annee = $data['id_annee'];
            $eleve->date_inscription = $dateReinscription;
            $eleve->save();
        });

        return redirect()->route('inscriptions.index', ['tab' => 'reinscription'])
            ->with('success', 'Réinscription enregistrée avec succès.');
    }

    private function generateMatricule(array $data): string
    {
        $year = now()->format('y');
        $seed = strtoupper(substr($data['nom_eleve'], 0, 2) . substr($data['prenom_eleve'], 0, 2));
        return 'ELV-' . $year . '-' . $seed . random_int(100, 999);
    }

    private function storeImage(Request $request): string
    {
        if (!$request->hasFile('image')) {
            return 'assets/images/avatars/avatar-1.png';
        }

        $directory = public_path('image_eleves');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('image');
        $name = uniqid('eleve_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return 'image_eleves/' . $name;
    }
}
