<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EmargementController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ProgrammeController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/login/select-school', [AuthController::class, 'selectSchool'])->name('login.select-school');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Élèves
    Route::match(['get', 'post'], '/eleves', [EleveController::class, 'index'])->name('eleves.index');
    Route::match(['get', 'post'], '/eleves/cartes-scolaires', [EleveController::class, 'cartes'])->name('eleves.cartes');
    Route::post('/eleves/cartes-scolaires/pdf', [EleveController::class, 'downloadCartesPdf'])->name('eleves.cartes.pdf');
    Route::post('/eleves/liste/pdf', [EleveController::class, 'downloadListPdf'])->name('eleves.list.pdf');
    Route::post('/eleves/liste/excel', [EleveController::class, 'downloadListExcel'])->name('eleves.list.excel');
    Route::get('/eleves/{id}/edit', [EleveController::class, 'edit'])->name('eleves.edit');
    Route::put('/eleves/{id}', [EleveController::class, 'update'])->name('eleves.update');
    Route::post('/eleves/{id}/transfert', [EleveController::class, 'transfer'])->name('eleves.transfer');
    Route::delete('/eleves/{id}', [EleveController::class, 'destroy'])->name('eleves.destroy');
    Route::get('/eleves/{id}', [EleveController::class, 'show'])->name('eleves.show');

    // Enseignants
    Route::get('/enseignants', [EnseignantController::class, 'index'])->name('enseignants.index');
    Route::post('/enseignants/search', [EnseignantController::class, 'index'])->name('enseignants.search');
    Route::get('/enseignants/create', [EnseignantController::class, 'create'])->name('enseignants.create');
    Route::post('/enseignants', [EnseignantController::class, 'store'])->name('enseignants.store');
    Route::get('/enseignants/emargements', [EmargementController::class, 'index'])->name('enseignants.emargements');
    Route::post('/enseignants/emargements/filter', [EmargementController::class, 'index'])->name('enseignants.emargements.filter');
    Route::post('/enseignants/emargements', [EmargementController::class, 'store'])->name('enseignants.emargements.store');
    Route::put('/enseignants/emargements/{id}', [EmargementController::class, 'update'])->name('enseignants.emargements.update');
    Route::patch('/enseignants/emargements/{id}/validate', [EmargementController::class, 'validateEmargement'])->name('enseignants.emargements.validate');
    Route::delete('/enseignants/emargements/{id}', [EmargementController::class, 'destroy'])->name('enseignants.emargements.destroy');
    Route::get('/enseignants/presences', [PresenceController::class, 'index'])->name('enseignants.presences');
    Route::post('/enseignants/presences/filter', [PresenceController::class, 'index'])->name('enseignants.presences.filter');
    Route::post('/enseignants/presences', [PresenceController::class, 'store'])->name('enseignants.presences.store');
    Route::put('/enseignants/presences/{id}', [PresenceController::class, 'update'])->name('enseignants.presences.update');
    Route::patch('/enseignants/presences/{id}/validate', [PresenceController::class, 'validatePresence'])->name('enseignants.presences.validate');
    Route::delete('/enseignants/presences/{id}', [PresenceController::class, 'destroy'])->name('enseignants.presences.destroy');
    Route::get('/enseignants/{id}', [EnseignantController::class, 'show'])->name('enseignants.show');
    Route::get('/enseignants/{id}/edit', [EnseignantController::class, 'edit'])->name('enseignants.edit');
    Route::put('/enseignants/{id}', [EnseignantController::class, 'update'])->name('enseignants.update');
    Route::patch('/enseignants/{id}/archive', [EnseignantController::class, 'archive'])->name('enseignants.archive');
    Route::patch('/enseignants/{id}/reactivate', [EnseignantController::class, 'reactivate'])->name('enseignants.reactivate');

    // Classes
    Route::get('/classes', [ClasseController::class, 'index'])->name('classes.index');
    Route::get('/classes/associations/officielles', [ClasseController::class, 'associations'])->name('classes.associations');
    Route::put('/classes/associations/officielles', [ClasseController::class, 'updateAssociations'])->name('classes.associations.update');
    Route::get('/classes/create', [ClasseController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClasseController::class, 'store'])->name('classes.store');
    Route::get('/classes/{id}', [ClasseController::class, 'show'])->name('classes.show');
    Route::get('/classes/{id}/edit', [ClasseController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{id}', [ClasseController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClasseController::class, 'destroy'])->name('classes.destroy');

    // Programmes officiels
    Route::get('/programmes', [ProgrammeController::class, 'index'])->name('programmes.index');
    Route::get('/programmes/create', [ProgrammeController::class, 'create'])->name('programmes.create');
    Route::post('/programmes', [ProgrammeController::class, 'store'])->name('programmes.store');
    Route::get('/programmes/pdf/download', [ProgrammeController::class, 'downloadPDF'])->name('programmes.pdf.download');
    Route::get('/programmes/{id}/edit', [ProgrammeController::class, 'edit'])->name('programmes.edit');
    Route::put('/programmes/{id}', [ProgrammeController::class, 'update'])->name('programmes.update');
    Route::delete('/programmes/{id}', [ProgrammeController::class, 'destroy'])->name('programmes.destroy');

    // Évaluations
    Route::match(['get', 'post'], '/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/create', [EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/evaluations/store', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('/evaluations/classes/{idClasse}/matieres', [EvaluationController::class, 'matieresByClasse'])->name('evaluations.classes.matieres');
    Route::post('/evaluations/eleves', [EvaluationController::class, 'students'])->name('evaluations.eleves');
    Route::get('/evaluations/{id}/programme', [EvaluationController::class, 'editProgramme'])->name('evaluations.programme.edit');
    Route::put('/evaluations/{id}/programme', [EvaluationController::class, 'updateProgramme'])->name('evaluations.programme.update');
    Route::get('/evaluations/{id}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('/evaluations/{id}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::delete('/evaluations/{id}', [EvaluationController::class, 'destroy'])->name('evaluations.destroy');
    Route::get('/evaluations/{id}', [EvaluationController::class, 'show'])->name('evaluations.show');

    // Finances
    Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
    Route::get('/finances/planifications', [FinanceController::class, 'listeLegacyPlanifications'])->name('finances.planifications');
    Route::post('/finances/planifications/filter', [FinanceController::class, 'listeLegacyPlanifications'])->name('finances.planifications.filter');
    Route::get('/finances/planifications/ajouter', [FinanceController::class, 'createLegacyPlanification'])->name('finances.planifications.create');
    Route::post('/finances/planifications', [FinanceController::class, 'storeLegacyPlanification'])->name('finances.planifications.store');
    Route::delete('/finances/planifications/{id}', [FinanceController::class, 'deleteLegacyPlanification'])->name('finances.planifications.destroy');
    Route::get('/finances/paiements', [FinanceController::class, 'listePaiements'])->name('finances.paiements');
    Route::post('/finances/paiements/filter', [FinanceController::class, 'filterPaiements'])->name('finances.paiements.filter');
    Route::match(['get', 'post'], '/finances/paiements/historique', [FinanceController::class, 'historiquePaiements'])->name('finances.paiements.historique');
    Route::get('/finances/paiements/historique/export', [FinanceController::class, 'exportHistoriquePaiements'])->name('finances.paiements.historique.export');
    Route::get('/finances/paiements/eleves/{id}/contexte', [FinanceController::class, 'contexteEleve'])->name('finances.paiements.eleves.contexte');
    Route::post('/finances/paiements/frais', [FinanceController::class, 'storeFraisScolaire'])->name('finances.paiements.frais.store');
    Route::post('/finances/paiements/reductions', [FinanceController::class, 'storeReductionConfig'])->name('finances.paiements.reductions.store');
    Route::post('/finances/paiements/plans', [FinanceController::class, 'generatePlanPaiement'])->name('finances.paiements.plans.store');
    Route::post('/finances/paiements/groupes', [FinanceController::class, 'storePaiementsGroupes'])->name('finances.paiements.groupes.store');
    Route::post('/finances/paiements', [FinanceController::class, 'storePaiement'])->name('finances.paiements.store');
    Route::put('/finances/paiements/{id}', [FinanceController::class, 'updatePaiement'])->name('finances.paiements.update');
    Route::delete('/finances/paiements/{id}', [FinanceController::class, 'cancelPaiement'])->name('finances.paiements.cancel');
    Route::get('/finances/caisse', [FinanceController::class, 'showCaisse'])->name('finances.caisse');
    Route::post('/finances/caisse', [FinanceController::class, 'storeCaisse'])->name('finances.caisse.store');
    Route::post('/finances/caisse/encaissements', [FinanceController::class, 'storeEncaissement'])->name('finances.encaissements.store');
    Route::post('/finances/caisse/decaissements', [FinanceController::class, 'storeDecaissement'])->name('finances.decaissements.store');
    Route::get('/finances/banques', [FinanceController::class, 'banques'])->name('finances.banques');
    Route::post('/finances/banques', [FinanceController::class, 'storeBanque'])->name('finances.banques.store');
    Route::put('/finances/banques/{id}', [FinanceController::class, 'updateBanque'])->name('finances.banques.update');
    Route::get('/finances/versements', [FinanceController::class, 'versements'])->name('finances.versements');
    Route::post('/finances/versements', [FinanceController::class, 'storeVersement'])->name('finances.versements.store');
    Route::get('/finances/retraits', [FinanceController::class, 'retraits'])->name('finances.retraits');
    Route::post('/finances/retraits', [FinanceController::class, 'storeRetrait'])->name('finances.retraits.store');
    Route::patch('/finances/retraits/{id}/validate', [FinanceController::class, 'validateRetrait'])->name('finances.retraits.validate');
    Route::get('/finances/paiements/{id}/thermique', [FinanceController::class, 'downloadRecuThermique'])->name('finances.paiements.thermique');
    Route::get('/finances/paiements/{id}/download', [FinanceController::class, 'downloadRecu'])->name('finances.paiements.download');
    Route::get('/pedagogie/classes/{idClasse}/bulletins', [BulletinController::class, 'index'])->name('pedagogie.bulletins.index');
    Route::get('/pedagogie/classes/{idClasse}/bulletins/data', [BulletinController::class, 'data'])->name('pedagogie.bulletins.data');
    Route::get('/pedagogie/bulletins/{id}/download', [BulletinController::class, 'downloadBulletin'])->name('pedagogie.bulletins.download');
    Route::get('/pedagogie/timetable', [TimetableController::class, 'index'])->name('pedagogie.timetable');
    Route::get('/pedagogie/timetable/download-pdf', [TimetableController::class, 'downloadPDF'])->name('pedagogie.timetable.download_pdf');
    Route::post('/pedagogie/timetable/filter', [TimetableController::class, 'index'])->name('pedagogie.timetable.filter');
    Route::post('/pedagogie/timetable', [TimetableController::class, 'store'])->name('pedagogie.timetable.store');
    Route::put('/pedagogie/timetable/{id}', [TimetableController::class, 'update'])->name('pedagogie.timetable.update');
    Route::delete('/pedagogie/timetable/{id}', [TimetableController::class, 'destroy'])->name('pedagogie.timetable.destroy');
    Route::post('/pedagogie/timetable/save-grid', [TimetableController::class, 'saveGrid'])->name('pedagogie.timetable.save_grid');
    Route::get('/pedagogie/parents', [ParentController::class, 'index'])->name('pedagogie.parents');
    Route::post('/pedagogie/parents/filter', [ParentController::class, 'index'])->name('pedagogie.parents.filter');
    Route::get('/pedagogie/parents/create', [ParentController::class, 'create'])->name('pedagogie.parents.create');
    Route::post('/pedagogie/parents', [ParentController::class, 'store'])->name('pedagogie.parents.store');
    Route::get('/pedagogie/parents/{id}/edit', [ParentController::class, 'edit'])->name('pedagogie.parents.edit');
    Route::put('/pedagogie/parents/{id}', [ParentController::class, 'update'])->name('pedagogie.parents.update');
    Route::delete('/pedagogie/parents/{id}', [ParentController::class, 'destroy'])->name('pedagogie.parents.destroy');
    Route::get('/pedagogie/matieres', [MatiereController::class, 'index'])->name('pedagogie.matieres');
    Route::post('/pedagogie/matieres', [MatiereController::class, 'store'])->name('pedagogie.matieres.store');
    Route::put('/pedagogie/matieres/{id}', [MatiereController::class, 'update'])->name('pedagogie.matieres.update');
    Route::delete('/pedagogie/matieres/{id}', [MatiereController::class, 'destroy'])->name('pedagogie.matieres.destroy');
    Route::get('/pedagogie/inscriptions', [InscriptionController::class, 'index'])->name('inscriptions.index');
    Route::get('/pedagogie/inscriptions/create', [InscriptionController::class, 'create'])->name('inscriptions.create');
    Route::post('/pedagogie/inscriptions', [InscriptionController::class, 'store'])->name('inscriptions.store');
    Route::get('/pedagogie/inscriptions/groupe', [InscriptionController::class, 'createGroup'])->name('inscriptions.group.create');
    Route::post('/pedagogie/inscriptions/groupe', [InscriptionController::class, 'storeGroup'])->name('inscriptions.group.store');
    Route::post('/pedagogie/inscriptions/groupe/import', [InscriptionController::class, 'importGroup'])->name('inscriptions.group.import');
    Route::get('/pedagogie/inscriptions/groupe/template', [InscriptionController::class, 'downloadGroupTemplate'])->name('inscriptions.group.template');
    Route::get('/pedagogie/inscriptions/reinscription', [InscriptionController::class, 'createReinscription'])->name('inscriptions.reinscription');
    Route::post('/pedagogie/inscriptions/reinscription/apercu', [InscriptionController::class, 'previewReinscription'])->name('inscriptions.reinscription.preview');
    Route::post('/pedagogie/inscriptions/reinscription', [InscriptionController::class, 'storeReinscription'])->name('inscriptions.reinscription.store');

    // Configuration
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::get('/configuration/ecoles', [ConfigurationController::class, 'ecoles'])->name('configuration.ecoles');
    Route::post('/configuration/ecoles', [ConfigurationController::class, 'storeEcole'])->name('configuration.ecoles.store');
    Route::put('/configuration/ecoles/{id}', [ConfigurationController::class, 'updateEcole'])->name('configuration.ecoles.update');
    Route::delete('/configuration/ecoles/{id}', [ConfigurationController::class, 'destroyEcole'])->name('configuration.ecoles.destroy');
    Route::get('/configuration/academies', [ConfigurationController::class, 'academies'])->name('configuration.academies');
    Route::post('/configuration/academies', [ConfigurationController::class, 'storeAcademie'])->name('configuration.academies.store');
    Route::put('/configuration/academies/{id}', [ConfigurationController::class, 'updateAcademie'])->name('configuration.academies.update');
    Route::delete('/configuration/academies/{id}', [ConfigurationController::class, 'destroyAcademie'])->name('configuration.academies.destroy');
    Route::get('/configuration/caps', [ConfigurationController::class, 'caps'])->name('configuration.caps');
    Route::post('/configuration/caps', [ConfigurationController::class, 'storeCap'])->name('configuration.caps.store');
    Route::put('/configuration/caps/{id}', [ConfigurationController::class, 'updateCap'])->name('configuration.caps.update');
    Route::delete('/configuration/caps/{id}', [ConfigurationController::class, 'destroyCap'])->name('configuration.caps.destroy');
    Route::get('/configuration/annees', [ConfigurationController::class, 'annees'])->name('configuration.annees');
    Route::post('/configuration/annees', [ConfigurationController::class, 'storeAnnee'])->name('configuration.annees.store');
    Route::get('/configuration/utilisateurs', [ConfigurationController::class, 'utilisateurs'])->name('configuration.utilisateurs');
    Route::get('/configuration/utilisateurs/create', [ConfigurationController::class, 'createUtilisateur'])->name('configuration.utilisateurs.create');
    Route::post('/configuration/utilisateurs', [ConfigurationController::class, 'storeUtilisateur'])->name('configuration.utilisateurs.store');
    Route::get('/configuration/utilisateurs/permissions/assigner', [ConfigurationController::class, 'assignUserPermissions'])->name('configuration.utilisateurs.permissions.assigner');
    Route::patch('/configuration/utilisateurs/{id}/status', [ConfigurationController::class, 'updateUserStatus'])->name('configuration.utilisateurs.status');
    Route::get('/configuration/utilisateurs/{id}/permissions', [ConfigurationController::class, 'editUserPermissions'])->name('configuration.utilisateurs.permissions');
    Route::put('/configuration/utilisateurs/{id}/permissions', [ConfigurationController::class, 'updateUserPermissions'])->name('configuration.utilisateurs.permissions.update');
    Route::get('/configuration/permissions', [ConfigurationController::class, 'permissions'])->name('configuration.permissions');
    Route::post('/configuration/permissions', [ConfigurationController::class, 'storePermission'])->name('configuration.permissions.store');

    // Types de notes
    Route::get('/configuration/types-notes', [ConfigurationController::class, 'typesNotes'])->name('configuration.types-notes');
    Route::post('/configuration/types-notes', [ConfigurationController::class, 'storeTypeNote'])->name('configuration.types-notes.store');
    Route::put('/configuration/types-notes/{id}', [ConfigurationController::class, 'updateTypeNote'])->name('configuration.types-notes.update');
    Route::delete('/configuration/types-notes/{id}', [ConfigurationController::class, 'destroyTypeNote'])->name('configuration.types-notes.destroy');

    // Classes officielles
    Route::get('/configuration/classes-officielles', [ConfigurationController::class, 'classesOfficielles'])->name('configuration.classes-officielles');

    // Status Controles
    Route::get('/configuration/status-controles', [ConfigurationController::class, 'statusControles'])->name('configuration.status-controles');
    Route::post('/configuration/status-controles', [ConfigurationController::class, 'storeStatusControle'])->name('configuration.status-controles.store');
    Route::put('/configuration/status-controles/{id}', [ConfigurationController::class, 'updateStatusControle'])->name('configuration.status-controles.update');
    Route::delete('/configuration/status-controles/{id}', [ConfigurationController::class, 'destroyStatusControle'])->name('configuration.status-controles.destroy');
});

Route::post('/theme/store', [ThemeController::class, 'store'])->name('theme.store');
