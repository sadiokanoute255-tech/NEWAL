<?php
use App\Http\Controllers\Web\ThemeController;
use App\Http\Middleware\CheckEventAccess;
use App\Http\Controllers\Web\TeamController;
use App\Http\Controllers\Web\AdminComplaintController;
use App\Http\Controllers\Web\AdminJournalController;
use App\Http\Controllers\Web\ComplaintController;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Middleware\BlockSuspended;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CheckInController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Web\GuestController;
use App\Http\Controllers\Web\GuestGroupController;
use App\Http\Controllers\Web\GuestImportController;
use App\Http\Controllers\Web\InvitationController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OffreController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\PublicInvitationController;
use App\Http\Controllers\Web\SectionController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\StatsController;
use Illuminate\Support\Facades\Route;

// Accueil + inscription sur la même page. Un utilisateur connecté va à son espace.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('tableau-de-bord')
        : view('auth.inscription');
})->name('accueil');

// Page publique de l'invitation : aucun compte requis
Route::get('/i/{token}', [PublicInvitationController::class, 'show'])->middleware('throttle:60,1')->name('invitation.show');
Route::post('/i/{token}/reponse', [PublicInvitationController::class, 'rsvp'])->middleware('throttle:20,1')->name('invitation.rsvp');
Route::get('/i/{token}/calendrier', [PublicInvitationController::class, 'calendar'])->middleware('throttle:30,1')->name('invitation.calendar');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('inscription');
    Route::post('/inscription', [AuthController::class, 'register'])->middleware('throttle:10,1');
// Connexion de l'administration ATAABA
Route::get('/admin', fn () => redirect()->route(auth()->check() && auth()->user()->is_admin ? 'admin.tableau' : 'admin.login'));
Route::get('/admin/connexion', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/connexion', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');});

Route::middleware(['auth', BlockSuspended::class, CheckEventAccess::class])->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('deconnexion');

    Route::prefix('espace')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('tableau-de-bord');
        Route::post('theme', [ThemeController::class, 'update'])->name('theme.update');
        // Pages du menu : choix de l'événement
        Route::get('invites', [SectionController::class, 'invites'])->name('section.invites');
        Route::get('invitations', [SectionController::class, 'invitations'])->name('section.invitations');
        Route::get('controle-acces', [SectionController::class, 'controle'])->name('section.controle');
        Route::get('statistiques', [SectionController::class, 'statistiques'])->name('section.statistiques');

        // Événements
        Route::resource('evenements', EventController::class)
            ->parameters(['evenements' => 'event']);
        Route::post('evenements/{event}/publier', [EventController::class, 'publish'])->name('evenements.publier');
        Route::post('evenements/{event}/dupliquer', [EventController::class, 'duplicate'])->name('evenements.dupliquer');

        // Invités
        Route::get('evenements/{event}/invites', [GuestController::class, 'index'])->name('invites.index');
        Route::get('evenements/{event}/invites/nouveau', [GuestController::class, 'create'])->name('invites.create');
        Route::post('evenements/{event}/invites', [GuestController::class, 'store'])->name('invites.store');
        Route::get('invites/{guest}/modifier', [GuestController::class, 'edit'])->name('invites.edit');
        Route::put('invites/{guest}', [GuestController::class, 'update'])->name('invites.update');
        Route::delete('invites/{guest}', [GuestController::class, 'destroy'])->name('invites.destroy');

        // Import des invités
        Route::get('evenements/{event}/import', [GuestImportController::class, 'create'])->name('invites.import');
        Route::get('evenements/{event}/import/modele', [GuestImportController::class, 'template'])->name('invites.modele');
        Route::post('evenements/{event}/import', [GuestImportController::class, 'store'])->middleware('throttle:10,1')->name('invites.import.store');

        // Cercles d'invités
        Route::get('evenements/{event}/cercles', [GuestGroupController::class, 'index'])->name('cercles.index');
        Route::post('evenements/{event}/cercles', [GuestGroupController::class, 'store'])->name('cercles.store');
        Route::put('cercles/{group}', [GuestGroupController::class, 'update'])->name('cercles.update');
        Route::delete('cercles/{group}', [GuestGroupController::class, 'destroy'])->name('cercles.destroy');

        // Invitations
        Route::get('evenements/{event}/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::post('evenements/{event}/invitations/generer', [InvitationController::class, 'generate'])->name('invitations.generate');
        Route::post('invitations/{invitation:id}/whatsapp', [InvitationController::class, 'whatsapp'])->name('invitations.whatsapp');

        // Contrôle d'accès
        Route::get('evenements/{event}/controle', [CheckInController::class, 'index'])->name('controle.index');
        Route::post('evenements/{event}/controle/recherche', [CheckInController::class, 'lookup'])->middleware('throttle:120,1')->name('controle.recherche');
        Route::post('evenements/{event}/controle/entree', [CheckInController::class, 'store'])->middleware('throttle:60,1')->name('controle.entree');
        Route::post('entrees/{checkIn}/annuler', [CheckInController::class, 'cancel'])->name('controle.annuler');

        // Statistiques
        Route::get('evenements/{event}/statistiques', [StatsController::class, 'show'])->name('statistiques.index');

        // Rapports et exports
        Route::get('evenements/{event}/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('evenements/{event}/exports/{rapport}/{format}', [ExportController::class, 'download'])
            ->where('rapport', 'invites|confirmations|refus|sans-reponse|presents|statistiques')
            ->where('format', 'xlsx|csv|pdf')
            ->middleware('throttle:20,1')
            ->name('exports.download');

        // Offres
        Route::get('evenements/{event}/offre', [OffreController::class, 'show'])->name('offres.show');
        Route::post('evenements/{event}/offre/{plan}', [OffreController::class, 'choose'])->name('offres.choose');

        // Notifications, profil et paramètres
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
               Route::get('notifications/compteur', [NotificationController::class, 'compteur'])->middleware('throttle:120,1')->name('notifications.compteur');
        Route::get('profil', [ProfileController::class, 'show'])->name('profil.show');
        Route::put('profil', [ProfileController::class, 'update'])->name('profil.update');
        Route::put('profil/mot-de-passe', [ProfileController::class, 'password'])->name('profil.password');
        Route::get('parametres', [SettingsController::class, 'show'])->name('parametres.show');
        Route::put('parametres', [SettingsController::class, 'update'])->name('parametres.update');
        // Réclamations de l'organisateur
        Route::get('reclamations', [ComplaintController::class, 'index'])->name('reclamations.index');
        Route::post('reclamations', [ComplaintController::class, 'store'])->middleware('throttle:10,1')->name('reclamations.store');
           // Équipe (propriétaire seulement)
        Route::get('equipe', [TeamController::class, 'index'])->name('equipe.index');
        Route::post('equipe', [TeamController::class, 'store'])->middleware('throttle:20,1')->name('equipe.store');
        Route::put('equipe/{membre}', [TeamController::class, 'update'])->name('equipe.update');
        Route::delete('equipe/{membre}', [TeamController::class, 'destroy'])->name('equipe.destroy');
        // Administration ATAABA (réservée aux administrateurs)
        Route::prefix('admin')->group(function () {
                        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.tableau');
            Route::get('utilisateurs', [AdminController::class, 'users'])->name('admin.utilisateurs');
            Route::put('utilisateurs/{user}/suspension', [AdminController::class, 'toggleUser'])->name('admin.utilisateurs.suspension');
                        Route::get('reclamations', [AdminComplaintController::class, 'index'])->name('admin.reclamations');
            Route::put('reclamations/{complaint}', [AdminComplaintController::class, 'update'])->name('admin.reclamations.update');
            Route::get('historique', [AdminJournalController::class, 'index'])->name('admin.historique');
            Route::get('offres', [AdminController::class, 'plans'])->name('admin.offres');
            Route::post('offres', [AdminController::class, 'storePlan'])->name('admin.offres.store');
            Route::put('offres/{plan}', [AdminController::class, 'updatePlan'])->name('admin.offres.update');
            Route::get('evenements', [AdminController::class, 'events'])->name('admin.evenements');
            Route::put('evenements/{event}/offre', [AdminController::class, 'assignPlan'])->name('admin.evenements.offre');
        });
    });
});