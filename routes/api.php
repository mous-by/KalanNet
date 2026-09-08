<?php

use App\Http\Controllers\Api\V1\AbonnementController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AppelEpreuveController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BulletinController;
use App\Http\Controllers\Api\V1\ClasseController;
use App\Http\Controllers\Api\V1\ConfigurationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EleveController;
use App\Http\Controllers\Api\V1\EmargementController;
use App\Http\Controllers\Api\V1\EnseignantController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\MatiereController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ParentController;
use App\Http\Controllers\Api\V1\PresenceController;
use App\Http\Controllers\Api\V1\ProgrammeController;
use App\Http\Controllers\Api\V1\ResultatNationalController;
use App\Http\Controllers\Api\V1\TeacherSalaryController;
use App\Http\Controllers\Api\V1\TimetableController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\BulletinController as WebBulletinController;
use App\Http\Controllers\EleveController as WebEleveController;
use App\Http\Controllers\ProgrammeController as WebProgrammeController;
use App\Http\Controllers\TeacherSalaryController as WebTeacherSalaryController;
use App\Http\Middleware\Api\BridgeSanctumAuth;
use App\Http\Middleware\Api\EnsureActiveSubscriptionApi;
use App\Http\Middleware\Api\SeedSchoolSession;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/select-school', [AuthController::class, 'selectSchool']);

    Route::middleware(['auth:sanctum', BridgeSanctumAuth::class, SeedSchoolSession::class, EnsureActiveSubscriptionApi::class])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/theme', [AuthController::class, 'updateTheme']);
        Route::put('/auth/locale', [AuthController::class, 'updateLocale']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::put('/dashboard/abonnements/{abonnement}/dates', [DashboardController::class, 'updateSubscriptionDates']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

        Route::get('/eleves', [EleveController::class, 'index']);
        Route::post('/eleves', [EleveController::class, 'store']);
        Route::get('/eleves/inscription-options', [EleveController::class, 'inscriptionOptions']);
        Route::get('/eleves/cartes-scolaires', [EleveController::class, 'cartes']);
        Route::post('/eleves/cartes-scolaires/pdf', [WebEleveController::class, 'downloadCartesPdf']);
        Route::post('/eleves/liste/pdf', [WebEleveController::class, 'downloadListPdf']);
        Route::post('/eleves/liste/excel', [WebEleveController::class, 'downloadListExcel']);
        Route::get('/eleves/transferts/{id}/fiche', [WebEleveController::class, 'transferCertificate']);
        Route::get('/eleves/{id}', [EleveController::class, 'show']);
        Route::put('/eleves/{id}', [EleveController::class, 'update']);
        Route::post('/eleves/{id}/transfert', [EleveController::class, 'transfer']);
        Route::post('/eleves/{id}/reintegrer', [EleveController::class, 'reintegrate']);
        Route::delete('/eleves/{id}', [EleveController::class, 'destroy']);

        Route::get('/enseignants', [EnseignantController::class, 'index']);
        Route::post('/enseignants', [EnseignantController::class, 'store']);
        Route::get('/enseignants/{id}', [EnseignantController::class, 'show']);
        Route::put('/enseignants/{id}', [EnseignantController::class, 'update']);
        Route::patch('/enseignants/{id}/archive', [EnseignantController::class, 'archive']);
        Route::patch('/enseignants/{id}/reactivate', [EnseignantController::class, 'reactivate']);

        Route::get('/classes', [ClasseController::class, 'index']);
        Route::get('/classes/form-options', [ClasseController::class, 'formOptions']);
        Route::post('/classes', [ClasseController::class, 'store']);
        Route::get('/classes/{id}', [ClasseController::class, 'show']);
        Route::put('/classes/{id}', [ClasseController::class, 'update']);
        Route::delete('/classes/{id}', [ClasseController::class, 'destroy']);

        Route::get('/matieres', [MatiereController::class, 'index']);
        Route::post('/matieres', [MatiereController::class, 'store']);
        Route::put('/matieres/{id}', [MatiereController::class, 'update']);
        Route::delete('/matieres/{id}', [MatiereController::class, 'destroy']);

        Route::get('/parents', [ParentController::class, 'index']);
        Route::post('/parents', [ParentController::class, 'store']);
        Route::get('/parents/form-options', [ParentController::class, 'formOptions']);
        Route::get('/parents/{id}', [ParentController::class, 'show']);
        Route::put('/parents/{id}', [ParentController::class, 'update']);
        Route::delete('/parents/{id}', [ParentController::class, 'destroy']);

        Route::get('/timetable', [TimetableController::class, 'index']);
        Route::get('/timetable/download-pdf', [TimetableController::class, 'downloadPDF']);
        Route::post('/timetable', [TimetableController::class, 'store']);
        Route::post('/timetable/save-grid', [TimetableController::class, 'saveGrid']);
        Route::put('/timetable/{id}', [TimetableController::class, 'update']);
        Route::delete('/timetable/{id}', [TimetableController::class, 'destroy']);

        Route::get('/evaluations', [EvaluationController::class, 'index']);
        Route::post('/evaluations', [EvaluationController::class, 'store']);
        Route::get('/evaluations/students', [EvaluationController::class, 'students']);
        Route::get('/evaluations/classes/{idClasse}/matieres', [EvaluationController::class, 'matieresByClasse']);
        Route::get('/evaluations/{id}', [EvaluationController::class, 'show']);
        Route::put('/evaluations/{id}/notes', [EvaluationController::class, 'update']);
        Route::post('/evaluations/{id}/validate', [EvaluationController::class, 'validateNotes']);
        Route::delete('/evaluations/{id}', [EvaluationController::class, 'destroy']);

        Route::get('/emargements', [EmargementController::class, 'index']);
        Route::post('/emargements', [EmargementController::class, 'store']);
        Route::put('/emargements/{id}', [EmargementController::class, 'update']);
        Route::post('/emargements/{id}/validate', [EmargementController::class, 'validateEmargement']);
        Route::delete('/emargements/{id}', [EmargementController::class, 'destroy']);

        Route::get('/presences', [PresenceController::class, 'index']);
        Route::post('/presences', [PresenceController::class, 'store']);
        Route::put('/presences/{id}', [PresenceController::class, 'update']);
        Route::post('/presences/{id}/validate', [PresenceController::class, 'validatePresence']);
        Route::delete('/presences/{id}', [PresenceController::class, 'destroy']);

        Route::get('/bulletins/classes', [BulletinController::class, 'classes']);
        Route::get('/bulletins/classes/{idClasse}', [BulletinController::class, 'index']);
        Route::get('/bulletins/classes/{idClasse}/data', [BulletinController::class, 'data']);
        Route::get('/bulletins/classes/{idClasse}/students', [BulletinController::class, 'studentsForBulletin']);
        Route::post('/bulletins/classes/{idClasse}/publish', [BulletinController::class, 'publishClassBulletins']);
        Route::delete('/bulletins/classes/{idClasse}/publish', [BulletinController::class, 'unpublishClassBulletins']);
        Route::post('/bulletins/classes/{idClasse}/pdf', [WebBulletinController::class, 'downloadClassBulletins']);
        Route::get('/bulletins/{id}/telecharger', [WebBulletinController::class, 'downloadBulletin']);

        Route::get('/programmes', [ProgrammeController::class, 'index']);
        Route::get('/programmes/create', [ProgrammeController::class, 'create']);
        Route::post('/programmes', [ProgrammeController::class, 'store']);
        Route::get('/programmes/pdf/download', [WebProgrammeController::class, 'downloadPDF']);
        Route::get('/programmes/{id}/edit', [ProgrammeController::class, 'edit']);
        Route::put('/programmes/{id}', [ProgrammeController::class, 'update']);
        Route::delete('/programmes/{id}', [ProgrammeController::class, 'destroy']);

        Route::get('/finances/paiements', [FinanceController::class, 'paiements']);
        Route::post('/finances/paiements', [FinanceController::class, 'storePaiement']);
        Route::post('/finances/paiements/groupes', [FinanceController::class, 'storePaiementsGroupes']);
        Route::put('/finances/paiements/{id}', [FinanceController::class, 'updatePaiement']);
        Route::post('/finances/paiements/{id}/cancel', [FinanceController::class, 'cancelPaiement']);
        Route::get('/finances/eleves/{id}/contexte', [FinanceController::class, 'contexteEleve']);

        Route::get('/finances/caisse', [FinanceController::class, 'caisse']);
        Route::post('/finances/encaissements', [FinanceController::class, 'storeEncaissement']);
        Route::post('/finances/decaissements', [FinanceController::class, 'storeDecaissement']);
        Route::post('/finances/decaissements/{id}/validate', [FinanceController::class, 'validateDecaissement']);

        Route::get('/salaires', [TeacherSalaryController::class, 'index']);
        Route::get('/salaires/etat', [TeacherSalaryController::class, 'etat']);
        Route::get('/salaires/etat/pdf', [WebTeacherSalaryController::class, 'etatPdf']);
        Route::get('/salaires/bulletin', [WebTeacherSalaryController::class, 'bulletin']);
        Route::post('/salaires/payer', [TeacherSalaryController::class, 'storePayment']);

        Route::get('/configuration/ecoles', [ConfigurationController::class, 'ecoles']);
        Route::post('/configuration/ecoles', [ConfigurationController::class, 'storeEcole']);
        Route::put('/configuration/ecoles/{id}', [ConfigurationController::class, 'updateEcole']);
        Route::delete('/configuration/ecoles/{id}', [ConfigurationController::class, 'destroyEcole']);

        Route::get('/configuration/annees', [ConfigurationController::class, 'annees']);
        Route::post('/configuration/annees', [ConfigurationController::class, 'storeAnnee']);

        Route::get('/configuration/utilisateurs', [ConfigurationController::class, 'utilisateurs']);
        Route::post('/configuration/utilisateurs', [ConfigurationController::class, 'storeUtilisateur']);
        Route::put('/configuration/utilisateurs/{id}', [ConfigurationController::class, 'updateUtilisateur']);
        Route::patch('/configuration/utilisateurs/{id}/status', [ConfigurationController::class, 'updateUserStatus']);
        Route::delete('/configuration/utilisateurs/{id}', [ConfigurationController::class, 'destroyUtilisateur']);
        Route::get('/configuration/utilisateurs/{id}/permissions', [ConfigurationController::class, 'editUserPermissions']);
        Route::put('/configuration/utilisateurs/{id}/permissions', [ConfigurationController::class, 'updateUserPermissions']);

        Route::get('/configuration/permissions', [ConfigurationController::class, 'permissions']);
        Route::post('/configuration/permissions', [ConfigurationController::class, 'storePermission']);

        Route::get('/configuration/academies', [ConfigurationController::class, 'academies']);
        Route::post('/configuration/academies', [ConfigurationController::class, 'storeAcademie']);
        Route::put('/configuration/academies/{id}', [ConfigurationController::class, 'updateAcademie']);
        Route::delete('/configuration/academies/{id}', [ConfigurationController::class, 'destroyAcademie']);

        Route::get('/configuration/caps', [ConfigurationController::class, 'caps']);
        Route::post('/configuration/caps', [ConfigurationController::class, 'storeCap']);
        Route::put('/configuration/caps/{id}', [ConfigurationController::class, 'updateCap']);
        Route::delete('/configuration/caps/{id}', [ConfigurationController::class, 'destroyCap']);

        Route::get('/configuration/types-notes', [ConfigurationController::class, 'typesNotes']);
        Route::post('/configuration/types-notes', [ConfigurationController::class, 'storeTypeNote']);
        Route::put('/configuration/types-notes/{id}', [ConfigurationController::class, 'updateTypeNote']);
        Route::delete('/configuration/types-notes/{id}', [ConfigurationController::class, 'destroyTypeNote']);

        Route::get('/configuration/classes-officielles', [ConfigurationController::class, 'classesOfficielles']);
        Route::post('/configuration/classes-officielles', [ConfigurationController::class, 'storeClasseOfficielle']);
        Route::put('/configuration/classes-officielles/{id}', [ConfigurationController::class, 'updateClasseOfficielle']);
        Route::delete('/configuration/classes-officielles/{id}', [ConfigurationController::class, 'destroyClasseOfficielle']);

        Route::get('/configuration/status-controles', [ConfigurationController::class, 'statusControles']);
        Route::post('/configuration/status-controles', [ConfigurationController::class, 'storeStatusControle']);
        Route::put('/configuration/status-controles/{id}', [ConfigurationController::class, 'updateStatusControle']);
        Route::delete('/configuration/status-controles/{id}', [ConfigurationController::class, 'destroyStatusControle']);

        Route::get('/annonces', [AnnouncementController::class, 'index']);
        Route::post('/annonces', [AnnouncementController::class, 'store']);
        Route::post('/annonces/marquer-lues', [AnnouncementController::class, 'markVisibleAsRead']);
        Route::post('/annonces/{id}/publier', [AnnouncementController::class, 'publish']);
        Route::post('/annonces/{id}/archiver', [AnnouncementController::class, 'archive']);
        Route::delete('/annonces/{id}', [AnnouncementController::class, 'destroy']);

        Route::get('/resultats-nationaux', [ResultatNationalController::class, 'index']);
        Route::post('/resultats-nationaux', [ResultatNationalController::class, 'store']);
        Route::post('/resultats-nationaux/import', [ResultatNationalController::class, 'import']);

        Route::get('/appels-epreuves', [AppelEpreuveController::class, 'index']);
        Route::get('/appels-epreuves/create', [AppelEpreuveController::class, 'create']);
        Route::post('/appels-epreuves', [AppelEpreuveController::class, 'store']);

        Route::get('/abonnements', [AbonnementController::class, 'index']);
        Route::post('/abonnements/payer', [AbonnementController::class, 'payer']);
        Route::post('/abonnements/manuel', [AbonnementController::class, 'manualSubmit']);
        Route::get('/abonnements/paiements/{reference}', [AbonnementController::class, 'paiement']);
        Route::post('/abonnements/paiements/{paiement}/approuver', [AbonnementController::class, 'approvePaiement']);
        Route::post('/abonnements/paiements/{paiement}/rejeter', [AbonnementController::class, 'rejectPaiement']);
        Route::post('/abonnements/offres', [AbonnementController::class, 'storeOffre']);
        Route::put('/abonnements/offres/{offre}', [AbonnementController::class, 'updateOffre']);
        Route::patch('/abonnements/offres/{offre}/toggle', [AbonnementController::class, 'toggleOffre']);

        Route::post('/assistant/chat', [AssistantController::class, 'chat']);
    });
});
