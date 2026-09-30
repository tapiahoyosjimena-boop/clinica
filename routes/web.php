<?php

use App\Domains\Payments\Http\Controllers\AttentionSlipController;
use App\Domains\Payments\Http\Controllers\InvoicePdfDownloadController;
use App\Domains\Payments\Http\Controllers\LibelulaCallbackController;
use App\Domains\Payments\Http\Controllers\PatientInvoicePortalController;
use App\Domains\Payments\Http\Controllers\QrPaymentStatusController;
use App\Domains\Reportes\Http\Controllers\PanelReportPdfPreviewDownloadController;
use App\Domains\Results\Http\Controllers\PatientResultPdfDownloadController;
use App\Domains\Results\Http\Controllers\PatientResultsPortalController;
use App\Http\Controllers\DoctorPortal\DoctorDashboardController;
use App\Http\Controllers\DoctorPortal\DoctorLoginController;
use App\Http\Controllers\DoctorPortal\DoctorNotificationsController;
use App\Http\Controllers\DoctorPortal\DoctorPatientsController;
use App\Http\Controllers\DoctorPortal\DoctorProfileController;
use App\Http\Controllers\DoctorPortal\DoctorResultPdfController;
use App\Http\Controllers\DoctorPortal\DoctorResultsController;
use App\Http\Controllers\HomeSearchController;
use App\Domains\Auth\Support\SystemPermissions;
use App\Http\Controllers\PatientPortal\PatientDashboardController;
use App\Http\Controllers\PatientPortal\PatientLoginController;
use App\Http\Controllers\PatientPortal\PatientNotificationsController;
use App\Http\Controllers\PatientPortal\PatientProfileController;
use App\Http\Controllers\PortalPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/busqueda-inicio', HomeSearchController::class)
    ->middleware('throttle:30,1')
    ->name('home.search');

// Ruta pública — Libélula llama a esta URL vía GET al confirmar un pago QR.
Route::get('/pagos/callback', LibelulaCallbackController::class)
    ->name('payments.libelula.callback');

// ── Portal del Paciente — Login (público) ────────────────────────────────────
Route::get('/paciente/login', [PatientLoginController::class, 'showLogin'])
    ->name('patient.login');
Route::post('/paciente/login', [PatientLoginController::class, 'login'])
    ->name('patient.login.submit');
Route::post('/paciente/logout', [PatientLoginController::class, 'logout'])
    ->name('patient.logout')
    ->middleware('auth');

// ── Portal del Paciente — Recuperación de contraseña (público) ───────────────
Route::get('/paciente/contrasena/solicitar', [PortalPasswordResetController::class, 'showRequestForm'])
    ->defaults('portal', 'patient')
    ->name('patient.password.request');
Route::post('/paciente/contrasena/enviar', [PortalPasswordResetController::class, 'sendResetLink'])
    ->defaults('portal', 'patient')
    ->name('patient.password.send')
    ->middleware('throttle:5,1');
Route::get('/paciente/contrasena/restablecer/{token}', [PortalPasswordResetController::class, 'showResetForm'])
    ->defaults('portal', 'patient')
    ->name('patient.password.reset');
Route::post('/paciente/contrasena/actualizar', [PortalPasswordResetController::class, 'resetPassword'])
    ->defaults('portal', 'patient')
    ->name('patient.password.update');

// ── Portal del Paciente — Rutas protegidas ───────────────────────────────────
Route::middleware(['auth', 'patient'])->prefix('paciente')->group(function () {
    Route::get('/inicio', PatientDashboardController::class)
        ->middleware('portal.permission:'.SystemPermissions::PATIENT_DASHBOARD)
        ->name('patient.dashboard');

    Route::get('/resultados', [PatientResultsPortalController::class, 'index'])
        ->middleware('portal.permission:'.SystemPermissions::PATIENT_RESULTS)
        ->name('results.patient.portal');

    Route::get('/comprobantes', [PatientInvoicePortalController::class, 'index'])
        ->middleware('portal.permission:'.SystemPermissions::PATIENT_PAYMENTS)
        ->name('payments.patient.portal');

    Route::middleware('portal.permission:'.SystemPermissions::PATIENT_NOTIFICATIONS)->group(function () {
        Route::get('/notificaciones', [PatientNotificationsController::class, 'index'])
            ->name('patient.notifications');
        Route::get('/notificaciones/{id}/abrir', [PatientNotificationsController::class, 'open'])
            ->name('patient.notifications.open');
        Route::post('/notificaciones/{id}/leer', [PatientNotificationsController::class, 'markAsRead'])
            ->name('patient.notifications.read');
        Route::post('/notificaciones/leer-todas', [PatientNotificationsController::class, 'markAllRead'])
            ->name('patient.notifications.readAll');
        Route::post('/notificaciones/eliminar-todas', [PatientNotificationsController::class, 'deleteAll'])
            ->name('patient.notifications.deleteAll');
    });

    Route::middleware('portal.permission:'.SystemPermissions::PATIENT_PROFILE)->group(function () {
        Route::get('/perfil', [PatientProfileController::class, 'show'])
            ->name('patient.profile');
        Route::get('/perfil/editar', [PatientProfileController::class, 'edit'])
            ->name('patient.profile.edit');
        Route::put('/perfil', [PatientProfileController::class, 'update'])
            ->name('patient.profile.update');
    });
});

// ── Portal del Médico — Login (público) ─────────────────────────────────────
Route::get('/medico/login', [DoctorLoginController::class, 'showLogin'])
    ->name('doctor.login');
Route::post('/medico/login', [DoctorLoginController::class, 'login'])
    ->name('doctor.login.submit');
Route::post('/medico/logout', [DoctorLoginController::class, 'logout'])
    ->name('doctor.logout')
    ->middleware('auth');

// ── Portal del Médico — Recuperación de contraseña (público) ─────────────────
Route::get('/medico/contrasena/solicitar', [PortalPasswordResetController::class, 'showRequestForm'])
    ->defaults('portal', 'doctor')
    ->name('doctor.password.request');
Route::post('/medico/contrasena/enviar', [PortalPasswordResetController::class, 'sendResetLink'])
    ->defaults('portal', 'doctor')
    ->name('doctor.password.send')
    ->middleware('throttle:5,1');
Route::get('/medico/contrasena/restablecer/{token}', [PortalPasswordResetController::class, 'showResetForm'])
    ->defaults('portal', 'doctor')
    ->name('doctor.password.reset');
Route::post('/medico/contrasena/actualizar', [PortalPasswordResetController::class, 'resetPassword'])
    ->defaults('portal', 'doctor')
    ->name('doctor.password.update');

// ── Portal del Médico — Rutas protegidas ─────────────────────────────────────
Route::middleware(['auth', 'doctor'])->prefix('medico')->group(function () {
    Route::get('/dashboard', DoctorDashboardController::class)
        ->middleware('portal.permission:'.SystemPermissions::DOCTOR_DASHBOARD)
        ->name('doctor.dashboard');

    Route::get('/pacientes', DoctorPatientsController::class)
        ->middleware('portal.permission:'.SystemPermissions::DOCTOR_PATIENTS)
        ->name('doctor.patients');

    Route::get('/resultados', DoctorResultsController::class)
        ->middleware('portal.permission:'.SystemPermissions::DOCTOR_RESULTS)
        ->name('doctor.results');

    Route::middleware('portal.permission:'.SystemPermissions::DOCTOR_NOTIFICATIONS)->group(function () {
        Route::get('/notificaciones', [DoctorNotificationsController::class, 'index'])
            ->name('doctor.notifications');
        Route::get('/notificaciones/{id}/abrir', [DoctorNotificationsController::class, 'open'])
            ->name('doctor.notifications.open');
        Route::post('/notificaciones/{id}/leer', [DoctorNotificationsController::class, 'markAsRead'])
            ->name('doctor.notifications.read');
        Route::post('/notificaciones/leer-todas', [DoctorNotificationsController::class, 'markAllRead'])
            ->name('doctor.notifications.readAll');
        Route::post('/notificaciones/eliminar-todas', [DoctorNotificationsController::class, 'deleteAll'])
            ->name('doctor.notifications.deleteAll');
    });

    Route::middleware('portal.permission:'.SystemPermissions::DOCTOR_PROFILE)->group(function () {
        Route::get('/perfil', [DoctorProfileController::class, 'show'])
            ->name('doctor.profile');
        Route::get('/perfil/editar', [DoctorProfileController::class, 'edit'])
            ->name('doctor.profile.edit');
        Route::put('/perfil', [DoctorProfileController::class, 'update'])
            ->name('doctor.profile.update');
    });

    Route::get('/resultados/{result}/pdf', DoctorResultPdfController::class)
        ->middleware('portal.permission:'.SystemPermissions::DOCTOR_RESULTS)
        ->name('results.doctor.pdf');
});

// ── Descargas autenticadas (staff y paciente) ────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/comprobantes/{invoice}/pdf', InvoicePdfDownloadController::class)
        ->name('payments.invoices.pdf');

    Route::get('/comprobantes/{invoice}/ficha-atencion', AttentionSlipController::class)
        ->name('payments.attention-slip');

    Route::get('/paciente/resultados/{result}/pdf', PatientResultPdfDownloadController::class)
        ->name('results.patient.pdf');

    Route::get('/pagos/{invoice}/estado-qr', QrPaymentStatusController::class)
        ->name('payments.qr.status');

    Route::get('/panel/reportes/pdf-preview/{token}', PanelReportPdfPreviewDownloadController::class)
        ->name('reportes.panel-preview-pdf');
});
