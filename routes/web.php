<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'web'])
    // ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::resource('shelves', \Callcocam\Plannerate\Http\Controllers\ShelfController::class)
            ->names('shelves');

        Route::resource('sections', \Callcocam\Plannerate\Http\Controllers\SectionController::class)
            ->names('sections');

        Route::prefix('sections/{gondola}')->group(function () {
            Route::put('reorder', [\Callcocam\Plannerate\Http\Controllers\SectionController::class, 'reorder'])->name('sections.reorder');
        });

        Route::put('/gondolas/{gondola}/scale-factor', [\Callcocam\Plannerate\Http\Controllers\GondolaController::class, 'updateScaleFactor'])->name('gondolas.updateScaleFactor');
        // Add this route for section duplication
        Route::post('/sections/{section}/duplicate', [\Callcocam\Plannerate\Http\Controllers\SectionController::class, 'duplicate'])->name('sections.duplicate');

        Route::put('/shelves/{shelf}/section', [ \Callcocam\Plannerate\Http\Controllers\ShelfController::class, 'updateSection'])->name('shelves.update-section');

        Route::resource('segments', \Callcocam\Plannerate\Http\Controllers\SegmentController::class)
            ->names('segments');
            
        Route::resource('layers', \Callcocam\Plannerate\Http\Controllers\LayerController::class)
            ->names('layers');
    });