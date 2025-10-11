<?php

use App\Http\Controllers\ColorController;
use App\Http\Controllers\ImageProcessingController;
use App\Http\Controllers\LabController;
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
