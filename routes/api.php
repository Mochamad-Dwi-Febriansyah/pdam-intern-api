<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AssessmentAspectController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinalReportController;
use App\Http\Controllers\SchoolUniController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\TTEController;
use App\Http\Controllers\UserController;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); // auth.login
    Route::post('/register', [AuthController::class, 'register']); // auth.register
    Route::get('/validate-token', [AuthController::class, 'validateToken']); // auth.validate-token
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']); // auth.refresh-token
    
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('check.jwt.auth'); // auth.logout
    Route::get('/me', [AuthController::class, 'me'])->middleware('check.jwt.auth'); // auth.me
    Route::post('/current-session', [AuthController::class, 'currentSession'])->middleware('check.jwt.auth'); // auth.current-session

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); //auth.forgot-password
    Route::post('/reset-password', [AuthController::class, 'resetPassword']); // auth.reset-password
});

Route::middleware('check.jwt.auth')->prefix('auth')->group(function () {
    Route::get('/role', [AuthController::class, 'getRoleName']); // auth.get-role
    Route::get('/permissions', [AuthController::class, 'getPermissions']); // auth.get-permissions
    Route::get('/has-role/{role}', [AuthController::class, 'hasRole']); // auth.has-role
    Route::get('/has-permission/{permission}', [AuthController::class, 'hasPermission']); // auth.has-permission
});


Route::get('/applications', [ApplicationController::class, 'index']); // applications.view
Route::post('/applications', [ApplicationController::class, 'store']); // applications.create
Route::get('/applications/{id}', [ApplicationController::class, 'show']); // applications.show
Route::put('/applications/{id}', [ApplicationController::class, 'update']); // applications.update
Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']); // applications.delete
Route::get('/applications/{id}/status', [ApplicationController::class, 'checkStatus']); // applications.check-status

Route::put('/applications/{id}/update-status-mentor', [ApplicationController::class, 'updateStatusAndMentor']); // documents.update-status-mentor
Route::put('/applications/{id}/submission-receipt', [ApplicationController::class, 'submissionReceipt']); // application.submission-receipt

Route::put('/applications/{id}/field-letter', [ApplicationController::class, 'fieldLetter']); // application.field-letter

Route::post('/cek-keabsahan', [TTEController::class, 'cekKeabsahan']); // auth.login

Route::group(['middleware' => 'check.sso.token'], function () {  
    Route::get('/mentor/mentee', [ApplicationController::class, 'getMenteesByMentor']);
    // application.mentor-mentee

    Route::get('/daily-reports/mentor', [DailyReportController::class, 'getDailyReportsByMentor']);
    // daily-reports.view-mentor

    Route::post('/daily-reports/mentor-verification', [DailyReportController::class, 'mentorVerification']);
    // daily-reports.mentor-verification

    Route::get('/final-reports/mentor-verification', [FinalReportController::class, 'getFinalReportsByMentor']);
    // final-reports.view-mentor
 
    Route::put('/final-reports/{id}/mentor-verification', [FinalReportController::class, 'mentorVerification']);
    // final-reports.mentor-verification
    // Hanya bisa diakses oleh mentor
    
    Route::get('/final-reports/hr-verification', [FinalReportController::class, 'getFinalReportsByHr']);
    // final-reports.view-hr

    Route::put('/final-reports/{id}/hr-verification', [FinalReportController::class, 'hrVerification']);
    // final-reports.hr-verification
    // Hanya bisa diakses oleh HR (setelah mentor approved)

    Route::post('/signatures/sync', [SignatureController::class, 'syncFromSSO']);
    // signatures.sync
    
    Route::apiResource('/signatures', SignatureController::class);
    // signatures.view, signatures.show, signatures.create, signatures.update, signatures.delete
    
});

Route::group(['middleware' => 'check.jwt.auth'], function () {
    Route::get('/statistics', [StatisticController::class, 'getStatistics']); // statistics.index
 
    Route::apiResource('/users', UserController::class);
    // users.view, users.show, users.create, users.update, users.delete

    Route::apiResource('/schoolunis', SchoolUniController::class);
    // schoolunis.view, schoolunis.show, schoolunis.create, schoolunis.update, schoolunis.delete

    Route::put('/documents/export-work-certificate/{user_id}', [DocumentController::class, 'exportWorkCertificate']);
    Route::apiResource('/documents', DocumentController::class);
    // documents.view, documents.show, documents.create, documents.update, documents.delete

    Route::get('/attendances/today', [AttendanceController::class, 'today']); //attendances.today
    Route::apiResource('/attendances', AttendanceController::class);
      // attendances.view, attendances.show, attendances.create, attendances.update, attendances.delete

    Route::get('/daily-reports/export', [DailyReportController::class, 'export']);
    // daily-reports.export 

    Route::apiResource('/daily-reports', DailyReportController::class);
    // daily-reports.view, daily-reports.show, daily-reports.create, daily-reports.update, daily-reports.delete

  
    Route::apiResource('/assessment-aspects', AssessmentAspectController::class);
    // assessment-aspects.view, assessment-aspects.show, assessment-aspects.create, assessment-aspects.update, assessment-aspects.delete

    Route::apiResource('/certificate', CertificateController::class);
    // certificate.view, certificate.show, certificate.create, certificate.update, certificate.delete

 
});

Route::group(['middleware' => 'check.either.token'], function () {   
    Route::apiResource('/final-reports', FinalReportController::class);
    // final-reports.view, final-reports.show, final-reports.create, final-reports.update, final-reports.delete
   
});

