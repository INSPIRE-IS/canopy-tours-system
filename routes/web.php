<?php

use App\Http\Controllers\Admin\AuditLogsController;
use App\Http\Controllers\Admin\CloseoutsController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryOverridesController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\QuestionnaireController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SeasonsController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ProfileController;
use App\Models\Service;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/activities', function () {
    return Inertia::render('Activities');
})->name('activities');
Route::resource('questionnaire', QuestionnaireController::class);

Route::get('/continents', [QuestionnaireController::class, 'getContinents']);
Route::get('/countries/{continent_id}', [QuestionnaireController::class, 'getCountries']);
Route::get('/states/{country_id}', [QuestionnaireController::class, 'getStates']);

// Route::get('/questionnaire-welcome', function () {
//     return Inertia::render('Questionnaire-Welcome');
// });

// Route::get('/questionnaire/{bookingId}', function () {
//     $bookingId = request()->route('bookingId');
//     return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
// });

// Route::get('/scan-booking', function () {
//     return Inertia::render('QRScanner');
// });


Route::middleware('auth')->group(function () {
    // Permissions
    Route::resource('permissions', PermissionsController::class);
    // Roles
    Route::resource('roles', RolesController::class);
    // Team
    Route::get('/teams/{team_id}/change', [TeamController::class, 'change'])->name('teams.change');
    Route::resource('teams', TeamController::class);
    // Users
    Route::resource('users', UsersController::class);
    // Services
    Route::resource('services', ServicesController::class);
    // Seasons
    Route::resource('seasons', SeasonsController::class);
    // Inventory
    Route::resource('inventories', InventoryController::class);
    // Closeouts
    Route::resource('closeouts', CloseoutsController::class);
    // Inventory Overrides
    Route::resource('inventories-overrides', InventoryOverridesController::class);

    // Audit Logs
    Route::resource('audit-logs', AuditLogsController::class);

    Route::get('bookings/index', [HomeController::class, 'bookings'])->name('bookings.index');




    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
