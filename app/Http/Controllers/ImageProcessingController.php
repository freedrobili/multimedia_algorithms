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
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    private $maxFileSize = 5120;

    /**
     * Отображение формы загрузки изображения
     */
    public function showUploadForm()
    {
        return view('image-processing');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:' . implode(',', $this->allowedExtensions), // Разрешенные MIME-типы
                'max:' . $this->maxFileSize
            ]
        ]);

        try {
            $image = $request->file('image');

            $fileName = Str::random(20) . '_' . time() . '.' . $image->getClientOriginalExtension();

            $path = $image->storeAs('uploads/images', $fileName, 'public');

            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('Файл не был сохранен на диск');
            }

            $fileInfo = [
                'original_name' => $image->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'file_size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'extension' => $image->getClientOriginalExtension(),
                'uploaded_at' => now(),
            ];

            $fullPath = Storage::disk('public')->path($path);

            $originalHistogram = $this->createHistogram($fullPath, 'original');

            // Генерируем публичный URL для доступа к изображению
            $imageUrl = Storage::disk('public')->url($path);

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

    private function createHistogram($imagePath, $type = 'original')
    {
        try {
            if (!file_exists($imagePath)) {
                Log::error('Файл для гистограммы не существует: ' . $imagePath);
                return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imagePath);

            $redHistogram = array_fill(0, 256, 0);
            $greenHistogram = array_fill(0, 256, 0);
            $blueHistogram = array_fill(0, 256, 0);
            $grayHistogram = array_fill(0, 256, 0);

            $width = $image->width();
            $height = $image->height();

            $sampleStep = 1;
            if ($width * $height > 1000000) {
                $sampleStep = ceil(sqrt($width * $height / 1000000));
            }

            Log::info("Создание гистограммы: {$width}x{$height}, шаг: {$sampleStep}");

            $totalPixels = 0;
            $sampledPixels = 0;

            for ($x = 0; $x < $width; $x += $sampleStep) {
                for ($y = 0; $y < $height; $y += $sampleStep) {
                    try {
                        $color = $image->pickColor($x, $y);
                        $r = $color->red()->value();
                        $g = $color->green()->value();
                        $b = $color->blue()->value();

                        // ИСПРАВЛЕННАЯ ФОРМУЛА ДЛЯ GRAY - используем правильные коэффициенты
                        // Безопасное вычисление яркости
                        $gray = (int)round($r * 0.299 + $g * 0.587 + $b * 0.114);
                        $gray = max(0, min(255, $gray));  // ограничиваем диапазон


                        $redHistogram[$r]++;
                        $greenHistogram[$g]++;
                        $blueHistogram[$b]++;
                        $grayHistogram[$gray]++;
                        $sampledPixels++;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            $totalPixels = $sampledPixels; // Используем фактическое количество обработанных пикселей

            // ИСПРАВЛЕНИЕ 1: Сохраняем абсолютные значения для логирования
            $absoluteRed128 = $redHistogram[39];
            $absoluteGreen128 = $greenHistogram[39];
            $absoluteBlue128 = $blueHistogram[39];
            $absoluteGray128 = $grayHistogram[39];

            $redHistogramReal = $redHistogram;
            $greenHistogramReal = $greenHistogram;
            $blueHistogramReal = $blueHistogram;
            $grayHistogramReal = $grayHistogram;

            // ИСПРАВЛЕНИЕ 2: Нормализуем для визуализации (0-100%), но логируем абсолютные значения
//            if ($totalPixels > 0) {
//                // Для визуализации в интерфейсе - нормализуем до 100%
//                $redHistogram = array_map(function($val) use ($totalPixels) {
//                    return ($val / $totalPixels) * 100;
//                }, $redHistogram);
//
//                $greenHistogram = array_map(function($val) use ($totalPixels) {
//                    return ($val / $totalPixels) * 100;
//                }, $greenHistogram);
//
//                $blueHistogram = array_map(function($val) use ($totalPixels) {
//                    return ($val / $totalPixels) * 100;
//                }, $blueHistogram);
//
//                $grayHistogram = array_map(function($val) use ($totalPixels) {
//                    return ($val / $totalPixels) * 100;
//                }, $grayHistogram);
//            }

            // ИСПРАВЛЕНИЕ 3: Логируем АБСОЛЮТНЫЕ значения, а не нормализованные
            Log::info("Значения для уровня 39: R={$absoluteRed128}, G={$absoluteGreen128}, B={$absoluteBlue128}, Gray={$absoluteGray128}, TotalPixels={$totalPixels}, PercentAt128=" . ($absoluteRed128/$totalPixels*100));

            // Дополнительная отладочная информация
            $maxRed = max($redHistogram);
            $maxGray = max($grayHistogram);
            Log::info("Пиковые значения гистограммы: MaxRed={$maxRed}%, MaxGray={$maxGray}%");

            $histogramData = [
                'red' => $redHistogram,
                'green' => $greenHistogram,
                'blue' => $blueHistogram,
                'gray' => $grayHistogram,
                'metadata' => [
                    'total_pixels' => $totalPixels,
                    'width' => $width,
                    'height' => $height,
                    'sample_step' => $sampleStep
                ]
            ];

            Storage::disk('public')->makeDirectory('uploads/histograms');

            $histogramFileName = 'histogram_' . $type . '_' . Str::random(10) . '_' . time() . '.json';
            $histogramFilePath = 'uploads/histograms/' . $histogramFileName;

            Storage::disk('public')->put($histogramFilePath, json_encode($histogramData));

            $url = Storage::disk('public')->url($histogramFilePath);

            Log::info("Гистограмма создана: {$histogramFilePath}, URL: {$url}");

            return $url;

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
        // Проверяем, ожидает ли клиент JSON-ответ (AJAX запрос)
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

    private function applyBrightness($image, $parameters)
    {
        $brightness = $parameters['value'] ?? 0;

        // Конвертируем значение из диапазона -255..255 в -100..100
        $normalizedBrightness = (int)($brightness / 2.55);

        $image->brightness($normalizedBrightness);

        return $this->saveProcessedImage($image, 'brightness', $brightness);
    }

    /**
     * Частичное инвертирование по каналу
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
                    $r = 255 - $r;
                } elseif ($channelType === 'partial_green') {
                    $g = 255 - $g;
                }

                $newColor = new Color($r, $g, $b);

                $image->drawPixel($x, $y, $newColor);
            }
        }
    }

    /**
     * Пороговое преобразование
     */
    private function applyThreshold($image, $parameters)
    {
        // Получаем тип порогового преобразования и значение порога
        $type = $parameters['type'] ?? 'binary';   // Тип: 'binary' или 'slice'
        $threshold = $parameters['value'] ?? 128;  // Значение порога (0-255)

        $width = $image->width();
        $height = $image->height();

        // Проходим по каждому пикселю изображения
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                // Получаем цвет текущего пикселя
                $color = $image->pickColor($x, $y);

                // Извлекаем значения RGB-каналов
                $r = $color->red()->value();
                $g = $color->green()->value();
                $b = $color->blue()->value();

                // Вычисляем яркость пикселя по формуле NTSC
                $gray = (int)round($r * 0.299 + $g * 0.587 + $b * 0.114);
//                $gray = max(0, min(255, $gray));  // ограничиваем диапазон

                // ДЛЯ ОТЛАДКИ: логируем различия
//                if ($r != $g || $g != $b || $gray != $r) {
//                    Log::info("Разные значения: R={$r}, G={$g}, B={$b}, Gray={$gray} at x={$x}, y={$y}");
//                }
                if ($type === 'binary') {
                    // Бинарное преобразование: выше порога - белый, ниже - черный
                    $value = $gray > $threshold ? 255 : 0;
                    $newColor = new Color($value, $value, $value);
                } elseif ($type === 'slice') {
                    // Яркостные срезы: сохраняем оригинальный цвет в диапазоне ±20 от порога. Остальные пиксели затемняем в 2 раза
                    if ($gray >= $threshold - 20 && $gray <= $threshold + 20) {
                        $newColor = $color;
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
     * Изменение контраста $factor = (259 * ($contrast + 255)) / (255 * (259 - $contrast));
     *
     * $newValue = ($value - 128) * $factor + 128;
     */
    /**
     * Изменение контраста
     */
    private function applyContrast($image, $parameters)
    {
        $contrast = $parameters['value'] ?? 0;

        Log::info('$contrast', (array) $contrast);
        // Ограничиваем значение в диапазоне -100 до 100
        $contrast = max(-100, min(100, $contrast));

        $image->contrast($contrast);

        return $this->saveProcessedImage($image, 'contrast', $contrast);
    }

    /**
     * Инвертирование изображения
     */
    private function applyInversion($image, $parameters)
    {
        $type = $parameters['type'] ?? 'full';

        if ($type === 'full') {
            $image->invert();
        } else {
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

        Storage::disk('public')->makeDirectory('uploads/processed');

        $image->save(storage_path('app/public/' . $filePath));

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
        // Проверяем, ожидает ли клиент JSON-ответ (AJAX запрос)
        if (!$request->expectsJson()) {
            return response()->json(['error' => 'Требуется JSON запрос'], 400);
        }

        $request->validate([
            'histogram_url' => 'required|string'
        ]);

        try {
            $url = $request->histogram_url;

            Log::info("Запрос данных гистограммы: {$url}");

            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';

            if (strpos($path, '/storage/') === 0) {
                $path = substr($path, 9);
            }

            Log::info("Извлеченный путь: {$path}");

            if (Storage::disk('public')->exists($path)) {
                $content = Storage::disk('public')->get($path);
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    Log::info("Данные гистограммы успешно загружены");
                    return response()->json($data);
                } else {
                    Log::error('Ошибка декодирования JSON: ' . json_last_error_msg());
                    return response()->json(['error' => 'Неверный формат данных гистограммы'], 500);
                }
            }

            Log::error('Файл гистограммы не найден: ' . $path);
            return response()->json(['error' => 'Данные гистограммы не найдены'], 404);

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки данных гистограммы: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка загрузки данных гистограммы: ' . $e->getMessage()], 500);
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

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

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
