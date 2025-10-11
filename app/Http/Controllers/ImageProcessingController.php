<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Colors\Rgb\Color;

class ImageProcessingController extends Controller
{
    // Разрешенные типы файлов
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    // Максимальный размер файла (5MB)
    private $maxFileSize = 5120;

    /**
     * Отображение формы загрузки
     */
    public function showUploadForm()
    {
        return view('image-processing');
    }

    /**
     * Обработка загрузки изображения
     */
    public function uploadImage(Request $request)
    {
        // Валидация
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:' . implode(',', $this->allowedExtensions),
                'max:' . $this->maxFileSize
            ]
        ]);

        try {
            // Получаем файл
            $image = $request->file('image');

            // Генерируем уникальное имя файла
            $fileName = Str::random(20) . '_' . time() . '.' . $image->getClientOriginalExtension();

            // Сохраняем оригинальное изображение
            $path = $image->storeAs('uploads/images', $fileName, 'public');

            // Проверяем, что файл действительно сохранился
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('Файл не был сохранен на диск');
            }

            // Получаем информацию о файле
            $fileInfo = [
                'original_name' => $image->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'file_size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'extension' => $image->getClientOriginalExtension(),
                'uploaded_at' => now(),
            ];

            // Получаем полный путь к файлу для создания гистограммы
            $fullPath = Storage::disk('public')->path($path);

            // Создаем гистограмму оригинального изображения
            $originalHistogram = $this->createHistogram($fullPath, 'original');

            // Получаем URL для изображения
            $imageUrl = Storage::disk('public')->url($path);

            // Логируем для отладки
            Log::info('Изображение загружено:', [
                'path' => $path,
                'url' => $imageUrl,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            return redirect()->route('image.upload.form')
                ->with([
                    'success' => 'Изображение успешно загружено!',
                    'image_url' => $imageUrl,
                    'file_info' => $fileInfo,
                    'histogram_url' => $originalHistogram
                ]);

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки изображения: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Ошибка при загрузке изображения: ' . $e->getMessage());
        }
    }

    /**
     * Создание гистограммы изображения
     */
    private function createHistogram($imagePath, $type = 'original')
    {
        try {
            // Проверяем существование файла
            if (!file_exists($imagePath)) {
                Log::error('Файл для гистограммы не существует: ' . $imagePath);
                return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imagePath);

            // Инициализируем массивы для гистограмм каналов
            $redHistogram = array_fill(0, 256, 0);
            $greenHistogram = array_fill(0, 256, 0);
            $blueHistogram = array_fill(0, 256, 0);
            $grayHistogram = array_fill(0, 256, 0);

            // Собираем данные гистограммы
            $width = $image->width();
            $height = $image->height();

            // Ограничиваем размер для производительности (опционально)
            $sampleStep = 1;
            if ($width * $height > 1000000) {
                $sampleStep = 2; // Уменьшаем детализацию для больших изображений
            }

            for ($x = 0; $x < $width; $x += $sampleStep) {
                for ($y = 0; $y < $height; $y += $sampleStep) {
                    try {
                        $color = $image->pickColor($x, $y);

                        // Получаем числовые значения каналов (для Intervention 3.x используем ->value())
                        $r = $color->red()->value();
                        $g = $color->green()->value();
                        $b = $color->blue()->value();
                        $gray = (int)($r * 0.299 + $g * 0.587 + $b * 0.114);

                        $redHistogram[$r]++;
                        $greenHistogram[$g]++;
                        $blueHistogram[$b]++;
                        $grayHistogram[$gray]++;
                    } catch (\Exception $e) {
                        // Пропускаем проблемные пиксели
                        continue;
                    }
                }
            }

            // Нормализуем гистограммы
            $maxValue = max(max($redHistogram), max($greenHistogram), max($blueHistogram), max($grayHistogram));

            if ($maxValue > 0) {
                $redHistogram = array_map(function($val) use ($maxValue) {
                    return ($val / $maxValue) * 100;
                }, $redHistogram);
                $greenHistogram = array_map(function($val) use ($maxValue) {
                    return ($val / $maxValue) * 100;
                }, $greenHistogram);
                $blueHistogram = array_map(function($val) use ($maxValue) {
                    return ($val / $maxValue) * 100;
                }, $blueHistogram);
                $grayHistogram = array_map(function($val) use ($maxValue) {
                    return ($val / $maxValue) * 100;
                }, $grayHistogram);
            }

            // Сохраняем данные гистограммы в JSON
            $histogramData = [
                'red' => $redHistogram,
                'green' => $greenHistogram,
                'blue' => $blueHistogram,
                'gray' => $grayHistogram
            ];

            $histogramFileName = 'histogram_' . $type . '_' . Str::random(10) . '_' . time() . '.json';
            $histogramFilePath = 'uploads/histograms/' . $histogramFileName;

            Storage::disk('public')->put($histogramFilePath, json_encode($histogramData));

            return Storage::disk('public')->url($histogramFilePath);

        } catch (\Exception $e) {
            Log::error('Ошибка создания гистограммы: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Обработка преобразований изображения
     */
    public function processImage(Request $request)
    {
        // Убедимся, что запрос является AJAX
        if (!$request->expectsJson()) {
            return response()->json(['error' => 'Требуется JSON запрос'], 400);
        }

        $request->validate([
            'image_path' => 'required|string',
            'operation' => 'required|string',
            'parameters' => 'sometimes|array'
        ]);

        try {
            $imagePath = $request->image_path;
            $operation = $request->operation;
            $parameters = $request->parameters ?? [];

            // Убедимся, что путь корректен
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::error("Изображение не найдено: " . $imagePath);
                return response()->json(['error' => 'Изображение не найдено'], 404);
            }

            $fullPath = Storage::disk('public')->path($imagePath);

            if (!file_exists($fullPath)) {
                Log::error("Изображение не найдено по пути: " . $fullPath);
                return response()->json(['error' => 'Изображение не найдено'], 404);
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullPath);

            $result = [];

            Log::info("Применение операции: " . $operation . " с параметрами: " . json_encode($parameters));

            switch ($operation) {
                case 'brightness':
                    $result = $this->applyBrightness($image, $parameters);
                    break;
                case 'inversion':
                    $result = $this->applyInversion($image, $parameters);
                    break;
                case 'threshold':
                    $result = $this->applyThreshold($image, $parameters);
                    break;
                case 'contrast':
                    $result = $this->applyContrast($image, $parameters);
                    break;
                default:
                    Log::error("Неизвестная операция: " . $operation);
                    return response()->json(['error' => 'Неизвестная операция'], 400);
            }

            Log::info("Операция выполнена успешно: " . $operation);
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Ошибка обработки изображения: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Ошибка обработки изображения: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Просветление изображения (исправленная версия для Intervention 3.x)
     */
    private function applyBrightness($image, $parameters)
    {
        $brightness = $parameters['value'] ?? 0;

        // Используем встроенный метод brightness для Intervention 3.x
        // Intervention 3.x ожидает значение от -100 до 100
        $normalizedBrightness = (int)($brightness / 2.55); // Конвертируем из диапазона -255..255 в -100..100

        $image->brightness($normalizedBrightness);

        return $this->saveProcessedImage($image, 'brightness', $brightness);
    }

    /**
     * Частичное инвертирование по каналу (исправленная версия для Intervention 3.x)
     */
    private function applyPartialInversion($image, $channelType)
    {
        $width = $image->width();
        $height = $image->height();

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = $image->pickColor($x, $y);

                $r = $color->red()->value();
                $g = $color->green()->value();
                $b = $color->blue()->value();

                if ($channelType === 'partial_red') {
                    $r = 255 - $r; // Инвертируем красный канал
                } elseif ($channelType === 'partial_green') {
                    $g = 255 - $g; // Инвертируем зеленый канал
                }

                // Создаем новый цвет и устанавливаем его
                $newColor = new Color($r, $g, $b);
                $image->drawPixel($x, $y, $newColor);
            }
        }
    }

    /**
     * Пороговое преобразование (исправленная версия для Intervention 3.x)
     */
    private function applyThreshold($image, $parameters)
    {
        $type = $parameters['type'] ?? 'binary';
        $threshold = $parameters['value'] ?? 128;

        $width = $image->width();
        $height = $image->height();

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = $image->pickColor($x, $y);

                $r = $color->red()->value();
                $g = $color->green()->value();
                $b = $color->blue()->value();

                $gray = (int)($r * 0.299 + $g * 0.587 + $b * 0.114);

                if ($type === 'binary') {
                    // Бинарное изображение
                    $value = $gray > $threshold ? 255 : 0;
                    $newColor = new Color($value, $value, $value);
                } elseif ($type === 'slice') {
                    // Яркостные срезы
                    if ($gray >= $threshold - 20 && $gray <= $threshold + 20) {
                        $newColor = $color; // Сохраняем оригинальный цвет
                    } else {
                        $grayValue = (int)($gray * 0.5);
                        $newColor = new Color($grayValue, $grayValue, $grayValue);
                    }
                } else {
                    $newColor = $color;
                }

                $image->drawPixel($x, $y, $newColor);
            }
        }

        return $this->saveProcessedImage($image, 'threshold', $type . '_' . $threshold);
    }

    /**
     * Изменение контраста (исправленная версия для Intervention 3.x)
     */
    private function applyContrast($image, $parameters)
    {
        $type = $parameters['type'] ?? 'medium';

        // Коэффициенты контраста для разных типов
        $contrastMap = [
            'low' => 10,
            'medium' => 25,
            'high' => 50
        ];

        $contrast = $contrastMap[$type] ?? 25;

        // Используем встроенный метод contrast для Intervention 3.x
        $image->contrast($contrast);

        return $this->saveProcessedImage($image, 'contrast', $type);
    }

    /**
     * Инвертирование изображения (исправленная версия для Intervention 3.x)
     */
    private function applyInversion($image, $parameters)
    {
        $type = $parameters['type'] ?? 'full';

        if ($type === 'full') {
            // Полное инвертирование с использованием встроенного метода
            $image->invert();
        } else {
            // Частичное инвертирование
            $this->applyPartialInversion($image, $type);
        }

        return $this->saveProcessedImage($image, 'inversion', $type);
    }

    /**
     * Сохранение обработанного изображения и создание гистограммы
     */
    private function saveProcessedImage($image, $operation, $parameters)
    {
        $fileName = $operation . '_' . Str::random(10) . '_' . time() . '.png';
        $filePath = 'uploads/processed/' . $fileName;

        // Создаем директорию если не существует
        Storage::disk('public')->makeDirectory('uploads/processed');

        // Сохраняем обработанное изображение
        $image->save(storage_path('app/public/' . $filePath));

        // Создаем гистограмму для обработанного изображения
        $histogramPath = $this->createHistogram(storage_path('app/public/' . $filePath), $operation);

        return [
            'processed_url' => Storage::disk('public')->url($filePath),
            'histogram_url' => $histogramPath,
            'file_name' => $fileName
        ];
    }

    /**
     * Получение данных гистограммы
     */
    public function getHistogramData(Request $request)
    {
        // Убедимся, что запрос является AJAX
        if (!$request->expectsJson()) {
            return response()->json(['error' => 'Требуется JSON запрос'], 400);
        }

        $request->validate([
            'histogram_url' => 'required|string'
        ]);

        try {
            $url = $request->histogram_url;
            $baseUrl = Storage::disk('public')->url('');
            $path = str_replace($baseUrl, '', $url);

            if (Storage::disk('public')->exists($path)) {
                $data = json_decode(Storage::disk('public')->get($path), true);
                return response()->json($data);
            }

            return response()->json(['error' => 'Данные гистограммы не найдены'], 404);

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки данных гистограммы: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка загрузки данных гистограммы'], 500);
        }
    }

    /**
     * Удаление изображения
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string'
        ]);

        try {
            $filePath = $request->file_path;

            // Удаляем основное изображение
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            // Удаляем обработанные изображения и гистограммы
            $fileName = basename($filePath);
            $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);

            $this->deleteFilesByPattern('uploads/processed/', $baseFileName);
            $this->deleteFilesByPattern('uploads/histograms/', $baseFileName);

            return redirect()->route('image.upload.form')->with('success', 'Изображение и связанные файлы успешно удалены!');

        } catch (\Exception $e) {
            Log::error('Ошибка удаления изображения: ' . $e->getMessage());
            return back()->with('error', 'Ошибка при удалении изображения: ' . $e->getMessage());
        }
    }

    /**
     * Удаление файлов по шаблону
     */
    private function deleteFilesByPattern($directory, $pattern)
    {
        try {
            $files = Storage::disk('public')->files($directory);
            foreach ($files as $file) {
                if (strpos(basename($file), $pattern) !== false) {
                    Storage::disk('public')->delete($file);
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка удаления файлов по шаблону: ' . $e->getMessage());
        }
    }
}
