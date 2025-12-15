<?php

use App\Http\Controllers\ColorController;
use App\Http\Controllers\ImageFilterController;
use App\Http\Controllers\ImageProcessingController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LzwController;
use App\Http\Controllers\RleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LabController::class, 'index'])->name('lab.main');
Route::get('/lab1', [ColorController::class, 'index'])->name('color.converter');
Route::post('/convert', [ColorController::class, 'convert'])->name('color.convert');
Route::post('/convert-from-rgb', [ColorController::class, 'convertFromRgb'])->name('color.convert.from.rgb');
Route::get('/color-converter', [ColorController::class, 'index'])->name('color.converter');
Route::post('/color/convert', [ColorController::class, 'convert']);
Route::post('/color/convert-from-any', [ColorController::class, 'convertFromAny']);
Route::post('/color/convert-from-rgb', [ColorController::class, 'convertFromRgb']);
Route::post('/color/get-circle-coordinates', [ColorController::class, 'getCircleCoordinates']);
Route::post('/color/get-hex-from-rgb', [ColorController::class, 'getHexFromRgb']);

Route::get('/supported-formats', [ImageProcessingController::class, 'getSupportedFormats'])->name('image.supported.formats');
Route::get('/lab2', [ImageProcessingController::class, 'showUploadForm']);
//Route::get('/image-processing', [ImageProcessingController::class, 'showUploadForm'])->name('image.upload.form');
Route::get('/image-processing', [ImageProcessingController::class, 'showUploadForm'])->name('image.upload.form');
Route::post('/image-processing/upload', [ImageProcessingController::class, 'uploadImage'])->name('image.upload');
Route::post('/image-processing/process', [ImageProcessingController::class, 'processImage'])->name('image.process');
Route::post('/image-processing/histogram-data', [ImageProcessingController::class, 'getHistogramData'])->name('image.histogram.data');
Route::delete('/image-processing/delete', [ImageProcessingController::class, 'deleteImage'])->name('image.delete');

// ЛР 3
Route::get('/lab3', [RleController::class, 'index'])->name('rle.index');
Route::post('/lab3/encode-text', [RleController::class, 'encodeText']);
Route::post('/lab3/encode-image', [RleController::class, 'encodeImage']);

// ЛР 4 - LZW
Route::prefix('lab4')->group(function () {
    Route::get('/', [LzwController::class, 'index']);
    Route::post('/encode-text', [LzwController::class, 'encodeText']);
    Route::post('/decode-text', [LzwController::class, 'decodeText']);
    Route::post('/encode-image', [LzwController::class, 'encodeImage']);
    Route::post('/decode-image', [LzwController::class, 'decodeImage']);
});

Route::prefix('lab5')->group(function () {
    Route::get('/', [ImageFilterController::class, 'index'])->name('lab5.index');
    Route::post('/upload', [ImageFilterController::class, 'uploadImage'])->name('lab5.upload');
    Route::post('/apply-noise', [ImageFilterController::class, 'applyNoise'])->name('lab5.apply-noise');
    Route::post('/apply-filter', [ImageFilterController::class, 'applyFilter'])->name('lab5.apply-filter');
    Route::get('/images', [ImageFilterController::class, 'getProcessedImages'])->name('lab5.images');
    Route::post('/clear', [ImageFilterController::class, 'clearAll'])->name('lab5.clear');
});
