<?php

use App\Http\Controllers\Api\InstallReportsController;
use App\Http\Controllers\Api\PromptSubmissionsController;
use App\Http\Controllers\Api\ReleaseAppcastController;
use App\Http\Controllers\Api\ReleaseArtifactsController;
use App\Http\Middleware\RejectOversizedInstallReport;
use App\Http\Middleware\RejectOversizedPromptSubmission;
use App\Http\Middleware\RejectOversizedReleaseUpload;
use Illuminate\Support\Facades\Route;

Route::post('install-reports', [InstallReportsController::class, 'store'])
    ->middleware([RejectOversizedInstallReport::class, 'throttle:installReports'])
    ->name('installReports.store');

Route::post('prompt-submissions', [PromptSubmissionsController::class, 'store'])
    ->middleware([RejectOversizedPromptSubmission::class, 'throttle:promptSubmissions'])
    ->name('promptSubmissions.store');

Route::prefix('releases')->middleware(['releaseToken', RejectOversizedReleaseUpload::class, 'throttle:30,1'])->group(function (): void {
    Route::post('artifacts', ReleaseArtifactsController::class)->name('releaseArtifacts.store');
    Route::post('appcast', ReleaseAppcastController::class)->name('releaseAppcast.store');
});
