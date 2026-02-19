<?php

use App\Http\Controllers\Admin\AuditLogsController;
use App\Http\Controllers\Admin\BookingPaymentController;
use App\Http\Controllers\Admin\BookingsController;
use App\Http\Controllers\Admin\BookingVouchersController;
use App\Http\Controllers\Admin\CheckinsController;
use App\Http\Controllers\Admin\CloseoutsController;
use App\Http\Controllers\Admin\CustomerManifestController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\HelpQuestionsController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryOverridesController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MessengerController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\QuestionnaireController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SeasonsController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\AlertsController;
use App\Http\Controllers\Admin\BoardingPassController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\WholesalerContactsController;
use App\Http\Controllers\Admin\WholesalerController;
use App\Http\Controllers\Admin\WholesalerRateController;
use App\Http\Controllers\Front\ActivityController;
use App\Http\Controllers\Front\BookingQuestionnaireController;
use App\Http\Controllers\Front\FeedbackQuestionnaireController;
use App\Http\Controllers\Front\PaymentController;
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

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::get('/activities', function () {
//     return Inertia::render('Activities');
// })->name('activities');
// Route::get('/', [ActivityController::class, 'index'])->name('welcome');

//temp debug route
// use App\Mail\BookingMail;
// Route::get('/_debug/mail/invoice', function () {
//     // Fake data shaped EXACTLY like the mail expects
//     $data = [
//         'invoice' => [
//             'number' => 'INV-12345',
//             'date' => now()->format('Y-m-d'),
//         ],
//         'customer' => [
//             'name' => 'John Doe',
//         ],
//         'booking' => [
//             'reference' => 'ABC123',
//         ],
//     ];

//     return new BookingMail($data);
// });



Route::redirect('/', '/front/welcome');
Route::get('/front/welcome/{team_id?}', [ActivityController::class, 'index'])->name('front.welcome');
Route::get('/activity', [ActivityController::class, 'activity'])->name('front.activity');
Route::get('/activity/calendar', [ActivityController::class, 'calendar'])->name('front.activity.calendar');
// Route::get('/front/activity/selected-promo/{promo_id}', [ActivityController::class, 'selectedPromo']);
Route::get('/front/activity/selected-promo', [ActivityController::class, 'selectedPromo']);
Route::post('/booking/store', [ActivityController::class, 'store'])->name('front.booking.store');
Route::post('/booking/gift-store', [ActivityController::class, 'storeGift'])->name('front.booking.gift-store');

Route::get('/test-order', [ActivityController::class, 'showTestOrder']); // Review payment screen
Route::get('/payment/booking/{booking}', [PaymentController::class, 'booking'])->name('front.payment.booking');
Route::get('/payment/booking/signed/{booking}', [PaymentController::class, 'bookingSigned'])
    ->name('front.payment.booking.signed')
    ->middleware('signed');
Route::get('/payment/gift/{gift}', [PaymentController::class, 'gift'])->name('front.payment.gift');

// Fake PayFast screen
Route::get('/payment/simulated', [PaymentController::class, 'simulated'])->name('front.payment.simulated');

// Handle offline submission
Route::post('/payment/offline', [PaymentController::class, 'offline'])->name('front.payment.offline');
Route::get('front/invoices/download/{booking}', [PaymentController::class, 'invoiceDownload'])->name('front.invoices.download');
Route::get('front/details/download/{booking}', [PaymentController::class, 'detailDownload'])->name('front.details.download');
Route::get('front/voucher', [ActivityController::class, 'voucher'])->name('front.voucher');


Route::get('front/test-boarding-pass/{site?}', [ActivityController::class, 'testBoardingPass'])->name('front.test-boarding-pass');
Route::get('front/test-boarding-pass/pdf/{site?}', [ActivityController::class, 'testBoardingPassPdf'])->name('front.test-boarding-pass.pdf');

Route::put('front/customer-manifests/edit', [BookingQuestionnaireController::class, 'UpdateCustomerManifest'])->name('front.customer-manifests.edit');
Route::get('/check-reference/{reference}', [BookingQuestionnaireController::class, 'reference'])->name('check-reference');
//Route::get('front/booking-questionnaire/checkin', [BookingQuestionnaireController::class, 'checkin'])->name('front.booking-questionnaire.checkin');
Route::get('front/booking-questionnaire/signed', [BookingQuestionnaireController::class, 'signed'])->name('front.booking-questionnaire.signed');

Route::get('front/booking-questionnaire/checkin', [BookingQuestionnaireController::class, 'checkin'])
    ->name('front.booking-questionnaire.checkin')
    ->middleware('signed'); // Laravel will reject any URL whose signature doesn’t match

Route::get('booking-questionnaire', [BookingQuestionnaireController::class, 'index'])
    ->name('booking-questionnaire.index')
    ->middleware(['auth', 'verified']);
Route::resource('booking-questionnaire', BookingQuestionnaireController::class)->except(['index']);


Route::get('front/feedback-questionnaire', [FeedbackQuestionnaireController::class, 'index'])->name('front.feedback-questionnaire.index');
Route::get('front/feedback-questionnaire/signed', [FeedbackQuestionnaireController::class, 'signed'])->name('front.feedback-questionnaire.signed');
Route::get('front/feedback-questionnaire/feedback', [FeedbackQuestionnaireController::class, 'feedback'])
    ->name('front.feedback-questionnaire.feedback')
    ->middleware('signed'); // Laravel will reject any URL whose signature doesn’t match

// Route::resource('feedback-questionnaire', FeedbackQuestionnaireController::class);
Route::post('front/feedback-questionnaire', [FeedbackQuestionnaireController::class, 'store'])->name('front.feedback-questionnaire.store');

Route::get('/continents', [BookingQuestionnaireController::class, 'getContinents']);
Route::get('/countries/{continent_id}', [BookingQuestionnaireController::class, 'getCountries']);
Route::get('/states/{country_id}', [BookingQuestionnaireController::class, 'getStates']);

// options endpoints for selects (swap to policies/middleware as needed)
Route::get('services/options', fn() => \App\Models\Service::select('id', 'name')->orderBy('name')->get())->name('services.options');
Route::get('seasons/options', fn() => \App\Models\Season::select('id', 'name')->orderBy('name')->get())->name('seasons.options');
Route::get('wholesalers/options', fn() => \App\Models\Wholesaler::select('id', 'name')->orderBy('name')->get())->name('wholesalers.options');
// Route::get('invoices/{path}', function ($path) {
//     $path = storage_path('app/public/' . $path);

//     abort_unless(file_exists($path), 404);

//     return response()->file($path);
// })->name('invoices');
// Route::get('vouchers/{path}', function ($path) {
//     $path = storage_path('app/public/' . $path);

//     abort_unless(file_exists($path), 404);

//     return response()->file($path);
// })->name('vouchers');

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
Route::redirect('dashboard', '/admin/dashboard');

//Route::middleware('auth')->group(function () {, 'namespace' => 'Admin'
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'verified']], function () {


    Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');
    // Route::get('dashboard', function () {
    //     return Inertia::render('Dashboard');
    // })->middleware(['auth', 'verified'])->name('dashboard');
    // Permissions
    Route::resource('permissions', PermissionsController::class);
    // Roles
    Route::resource('roles', RolesController::class);
    // Team
    //Route::get('/teams/{team_id}/change', [TeamController::class, 'change'])->name('teams.change');
    Route::post('/teams/change', [TeamController::class, 'change'])->name('teams.change');
    Route::resource('teams', TeamController::class);
    // Boarding Pass Templates
    Route::resource('boardingpass', BoardingPassController::class);
    // Users
    Route::resource('users', UsersController::class);

    // User Alerts
    Route::get('user-alerts/read', [AlertsController::class, 'read'])->name('user-alerts.read');
    Route::resource('user-alerts', AlertsController::class); // ['except' => ['edit', 'update']]

    // Services
    Route::resource('services', ServicesController::class);
    // Seasons
    Route::resource('seasons', SeasonsController::class);
    // Inventory
    Route::resource('inventories', InventoryController::class);
    // Closeouts
    Route::resource('closeouts', CloseoutsController::class);
    // Wholesalers
    Route::resource('wholesalers', WholesalerController::class);
    Route::resource('wholesaler-contacts', WholesalerContactsController::class);
    // Vouchers
    Route::resource('vouchers', VoucherController::class);

    Route::resource('wholesaler-rates', WholesalerRateController::class)->names('wholesaler_rates');
    Route::resource('discounts', DiscountController::class);
    Route::get('promotions/valid', [PromotionController::class, 'valid'])->name('promotions.valid');
    Route::resource('promotions', PromotionController::class);

    Route::get('booking_vouchers/download/{bookingVoucher}', [BookingVouchersController::class, 'bookingVoucherDownload'])->name('booking_vouchers.download');
    Route::resource('booking_vouchers', BookingVouchersController::class);

    // Inventory Overrides
    Route::resource('inventories-overrides', InventoryOverridesController::class);

    // messages
    Route::get('messenger', [MessengerController::class, 'index'])->name('messenger.index');
    Route::get('messenger/create', [MessengerController::class, 'createTopic'])->name('messenger.createTopic');
    Route::post('messenger', [MessengerController::class, 'storeTopic'])->name('messenger.storeTopic');
    Route::get('messenger/inbox', [MessengerController::class, 'showInbox'])->name('messenger.showInbox');
    Route::get('messenger/outbox', [MessengerController::class, 'showOutbox'])->name('messenger.showOutbox');
    Route::get('messenger/{topic}', [MessengerController::class, 'showMessages'])->name('messenger.showMessages');
    Route::delete('messenger/{topic}', [MessengerController::class, 'destroyTopic'])->name('messenger.destroyTopic');
    Route::post('messenger/{topic}/reply', [MessengerController::class, 'replyToTopic'])->name('messenger.reply');
    Route::get('messenger/{topic}/showReply', [MessengerController::class, 'showReply'])->name('messenger.showReply');

    // reports
    Route::resource('reports', ReportsController::class);

    // Help Questions
    Route::resource('help-questions', HelpQuestionsController::class);

    // Audit Logs
    Route::resource('audit-logs', AuditLogsController::class);

    //Route::get('bookings/index', [HomeController::class, 'bookings'])->name('bookings.index');
    // Route::get('invoices/download/{booking}', [BookingsController::class, 'invoiceDownload'])->name('invoices.download');
    Route::get('details/download/{booking}', [BookingsController::class, 'detailDownload'])->name('details.download');


    Route::get('/booking/activity/calendar', [BookingsController::class, 'calendar'])->name('booking.activity.calendar');
    Route::post('/bookings/calculate-price', [BookingsController::class, 'calculate'])->name('booking.calculate');
    Route::post('/bookings/send-email', [BookingsController::class, 'sendEmail'])->name('bookings.send-email');
    Route::get('bookings/add/{booking}', [BookingsController::class, 'add'])->name('bookings.add');
    Route::post('bookings/notes', [BookingsController::class, 'addNote'])->name('bookings.notes.store');
    Route::post('bookings/cancel-slot', [BookingsController::class, 'cancelSlot'])->name('bookings.cancel-slot');
    // Route::get('bookings/cancel-booking/{booking}', [BookingsController::class, 'cancelBooking'])->name('bookings.cancel-booking');
    Route::resource('bookings', BookingsController::class);

    Route::post('customer-manifests/add', [CustomerManifestController::class, 'add'])->name('customer-manifests.add');
    Route::put('customer-manifests/edit-single/{booking_item_id}', [CustomerManifestController::class, 'editSingle'])->name('customer-manifests.edit-single');
    Route::put('customer-manifests/edit/{booking_item_id}', [CustomerManifestController::class, 'edit'])->name('customer-manifests.edit');
    Route::get('booking-payments/create/{booking}', [BookingPaymentController::class, 'create'])->name('booking-payments.create');
    Route::post('booking-payments/simulated/{booking}', [BookingPaymentController::class, 'simulate'])->name('booking-payments.simulated');
    Route::post('booking-payments/paylink/{booking}', [BookingPaymentController::class, 'sendPayLink'])->name('booking-payments.paylink');
    Route::post('booking-payments/store', [BookingPaymentController::class, 'store'])->name('booking-payments.store');
    Route::get('booking-payments/edit/{booking}', [BookingPaymentController::class, 'edit'])->name('booking-payments.edit');
    Route::put('booking-payments/update', [BookingPaymentController::class, 'update'])->name('booking-payments.update');
    // Route::resource('booking_payments', BookingPaymentController::class);

    Route::resource('invoice-templates', \App\Http\Controllers\Admin\InvoiceTemplateController::class);
    Route::get('/invoices/resend-invoice{booking_id}', [InvoiceController::class, 'resend_invoice'])->name('invoices.resend-invoice');
    Route::post('/bookings/preview-invoice', [InvoiceController::class, 'preview'])->name('bookings.preview-invoice');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'previewSaved'])->name('invoices.preview');
    Route::get('/invoices/download/{invoice}', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/questionnaires/answers/store', [QuestionnaireController::class, 'answerStore'])->name('questionnaires.answers.store');
    Route::resource('questionnaire', QuestionnaireController::class);
    Route::get('/checkins/index/{booking_id}/{customer_id?}', [CheckinsController::class, 'index'])->name('checkins.index');
    Route::post('/checkins/store', [CheckinsController::class, 'store'])->name('checkins.store');
    //Route::resource('checkins', CheckinsController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
