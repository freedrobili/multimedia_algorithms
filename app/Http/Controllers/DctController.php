<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;

class DctController extends Controller
{
    private ImageManager $imageManager;

    public function __construct()
    {
        // Инициализация ImageManager с драйвером GD
        $this->imageManager = new ImageManager(new Driver());
    }

    public function index()
    {
        return view('lab6.index');
    }

    // Задание 1: генерация сигнала
    public function generateSignal(Request $request)
    {
        try {
            $N = $request->input('N', 256);
            $signal = [];
            for ($i = 0; $i < $N; $i++) {
                $signal[] = sin(2 * pi() * 5 * $i / $N) + 0.5 * sin(2 * pi() * 20 * $i / $N);
            }
            return response()->json(['signal' => $signal]);
        } catch (\Exception $e) {
            Log::error('Ошибка в generateSignal: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка генерации сигнала'], 500);
        }
    }

    // Прямое DCT-II
    public function dct1D(Request $request)
    {
        try {
            $signal = $request->input('signal');

            if (!$signal || !is_array($signal)) {
                return response()->json(['error' => 'Неверный формат сигнала'], 400);
            }

            $N = count($signal);
            $dct = [];

            for ($k = 0; $k < $N; $k++) {
                $sum = 0;
                for ($n = 0; $n < $N; $n++) {
                    $sum += $signal[$n] * cos(pi() * $k * (2 * $n + 1) / (2 * $N));
                }
                $alpha = ($k == 0) ? sqrt(1 / $N) : sqrt(2 / $N);
                $dct[$k] = $alpha * $sum;
            }

            return response()->json(['dct' => $dct]);
        } catch (\Exception $e) {
            Log::error('Ошибка в dct1D: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка DCT преобразования'], 500);
        }
    }

    // Обратное DCT-III
    public function idct1D(Request $request)
    {
        try {
            $dct = $request->input('dct');

            if (!$dct || !is_array($dct)) {
                return response()->json(['error' => 'Неверный формат DCT коэффициентов'], 400);
            }

            $N = count($dct);
            $signal = [];

            for ($n = 0; $n < $N; $n++) {
                $sum = 0;
                for ($k = 0; $k < $N; $k++) {
                    $alpha = ($k == 0) ? sqrt(1 / $N) : sqrt(2 / $N);
                    $sum += $alpha * $dct[$k] * cos(pi() * $k * (2 * $n + 1) / (2 * $N));
                }
                $signal[$n] = $sum;
            }

            return response()->json(['signal' => $signal]);
        } catch (\Exception $e) {
            Log::error('Ошибка в idct1D: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка обратного DCT преобразования'], 500);
        }
    }

    // Задание 2: обнуление высокочастотных коэффициентов
    public function zeroHighFreq(Request $request)
    {
        try {
            $dct = $request->input('dct');
            $percent = $request->input('percent', 50);

            if (!$dct || !is_array($dct)) {
                return response()->json(['error' => 'Неверный формат DCT коэффициентов'], 400);
            }

            $N = count($dct);
            $zeroFrom = floor($N * (100 - $percent) / 100);

            for ($i = $zeroFrom; $i < $N; $i++) {
                $dct[$i] = 0;
            }

            return response()->json(['dct_zeroed' => $dct]);
        } catch (\Exception $e) {
            Log::error('Ошибка в zeroHighFreq: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка обнуления коэффициентов'], 500);
        }
    }

    // Задание 3: загрузка изображения
    // Задание 3: загрузка изображения (ИСПРАВЛЕННАЯ ВЕРСИЯ)
    // Задание 3: загрузка изображения (ИСПРАВЛЕННАЯ ВЕРСИЯ)
    // Задание 3: загрузка изображения (РАБОЧАЯ ВЕРСИЯ)
    public function uploadImage(Request $request)
    {
        try {
            // Проверяем наличие файла
            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'Файл не загружен'], 400);
            }

            $file = $request->file('image');

            // Проверяем валидность файла
            if (!$file->isValid()) {
                return response()->json(['error' => 'Неверный файл'], 400);
            }

            // Проверяем расширение файла
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions)) {
                return response()->json(['error' => 'Неподдерживаемый формат изображения: ' . $extension], 400);
            }

            // Создаем директорию, если её нет
            $directory = 'public/lab6';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Генерируем уникальное имя файла
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $path = $directory . '/' . $filename;

            // Сохраняем оригинальный файл
            $file->move(storage_path('app/' . $directory), $filename);

            // Полный путь к файлу
            $fullPath = storage_path('app/' . $path);

            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'Файл не был сохранен'], 500);
            }

            // ========== СПОСОБ 1: Простой и надежный через GD ==========

            // Получаем информацию об изображении
            $imageInfo = @getimagesize($fullPath);
            if (!$imageInfo) {
                return response()->json(['error' => 'Неверный формат изображения'], 400);
            }

            list($width, $height, $type) = $imageInfo;

            Log::info("Изображение: {$width}x{$height}, тип: {$type}");

            // Загружаем изображение в зависимости от типа
            $gdImage = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $gdImage = @imagecreatefromjpeg($fullPath);
                    break;
                case IMAGETYPE_PNG:
                    $gdImage = @imagecreatefrompng($fullPath);
                    // Сохраняем альфа-канал
                    imagealphablending($gdImage, false);
                    imagesavealpha($gdImage, true);
                    break;
                case IMAGETYPE_GIF:
                    $gdImage = @imagecreatefromgif($fullPath);
                    break;
                case IMAGETYPE_BMP:
                    $gdImage = @imagecreatefrombmp($fullPath);
                    break;
                case IMAGETYPE_WEBP:
                    $gdImage = @imagecreatefromwebp($fullPath);
                    break;
                default:
                    // Пробуем загрузить как строку
                    $fileContent = file_get_contents($fullPath);
                    if ($fileContent !== false) {
                        $gdImage = @imagecreatefromstring($fileContent);
                    }
            }

            if (!$gdImage) {
                return response()->json(['error' => 'Не удалось загрузить изображение'], 500);
            }

            // Создаем новое изображение для grayscale
            $grayImage = imagecreatetruecolor($width, $height);

            // Добавляем поддержку прозрачности для PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($grayImage, false);
                imagesavealpha($grayImage, true);
                $transparent = imagecolorallocatealpha($grayImage, 0, 0, 0, 127);
                imagefill($grayImage, 0, 0, $transparent);
            }

            // Конвертируем в grayscale и извлекаем пиксели
            $pixels = [];
            $sampleValues = [];
            $totalBrightness = 0;
            $nonZeroCount = 0;
            $minBrightness = 255;
            $maxBrightness = 0;

            for ($y = 0; $y < $height; $y++) {
                $row = [];
                for ($x = 0; $x < $width; $x++) {
                    // Получаем цвет пикселя
                    $rgb = imagecolorat($gdImage, $x, $y);

                    // Извлекаем каналы
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $a = ($rgb >> 24) & 0x7F; // Альфа канал

                    // Формула для яркости (стандартная для RGB -> grayscale)
                    $brightness = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);

                    // Учитываем альфа-канал для прозрачности
                    if ($type == IMAGETYPE_PNG && $a > 0) {
                        $brightness = (int)($brightness * (127 - $a) / 127);
                    }

                    // Сохраняем пиксель
                    $row[$x] = $brightness;

                    // Собираем статистику
                    $totalBrightness += $brightness;
                    if ($brightness > 0) $nonZeroCount++;
                    if ($brightness < $minBrightness) $minBrightness = $brightness;
                    if ($brightness > $maxBrightness) $maxBrightness = $brightness;

                    // Сохраняем несколько значений для отладки
                    if ($y < 2 && $x < 2) {
                        $sampleValues["{$y},{$x}"] = [
                            'brightness' => $brightness,
                            'rgb' => [$r, $g, $b]
                        ];
                    }

                    // Создаем grayscale пиксель для preview
                    $grayColor = imagecolorallocate($grayImage, $brightness, $brightness, $brightness);
                    imagesetpixel($grayImage, $x, $y, $grayColor);
                }
                $pixels[$y] = $row;
            }

            // Сохраняем grayscale версию для превью
            $grayFilename = 'gray_' . $filename;
            $grayPath = $directory . '/' . $grayFilename;

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($grayImage, storage_path('app/' . $grayPath), 90);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($grayImage, storage_path('app/' . $grayPath), 9);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($grayImage, storage_path('app/' . $grayPath));
                    break;
                default:
                    imagepng($grayImage, storage_path('app/' . $grayPath), 9);
            }

            // Очищаем память
            imagedestroy($gdImage);
            imagedestroy($grayImage);

            // Статистика
            $totalPixels = $width * $height;
            $meanBrightness = $totalPixels > 0 ? $totalBrightness / $totalPixels : 0;

            Log::info("Статистика изображения:");
            Log::info("  Размер: {$width}x{$height} = {$totalPixels} пикселей");
            Log::info("  Ненулевых: {$nonZeroCount} (" . round($nonZeroCount/$totalPixels*100, 1) . "%)");
            Log::info("  Яркость: мин={$minBrightness}, макс={$maxBrightness}, среднее=" . round($meanBrightness, 2));
            Log::info("  Примеры пикселей:", $sampleValues);

            // Проверяем, что изображение не полностью черное
            if ($nonZeroCount == 0) {
                Log::warning("Изображение полностью черное или все пиксели равны 0!");
            }

            // Создаем уменьшенное превью с помощью Intervention Image
            $previewFilename = 'preview_' . $filename;
            $previewPath = $directory . '/' . $previewFilename;

            try {
                $previewImage = $this->imageManager->read($fullPath);
                $previewImage->scale(300, 300);
                $previewImage->save(storage_path('app/' . $previewPath));
            } catch (\Exception $e) {
                Log::warning("Не удалось создать превью через Intervention: " . $e->getMessage());
                // Создаем превью через GD
                $previewGd = imagecreatetruecolor(300, 300);
                $sourceGd = imagecreatefromstring(file_get_contents($fullPath));
                imagecopyresampled($previewGd, $sourceGd, 0, 0, 0, 0, 300, 300, $width, $height);
                imagejpeg($previewGd, storage_path('app/' . $previewPath), 90);
                imagedestroy($previewGd);
                imagedestroy($sourceGd);
            }

            return response()->json([
                'success' => true,
                'path' => Storage::url($previewPath),
                'gray_path' => Storage::url($grayPath),
                'original_path' => Storage::url($path),
                'pixels' => $pixels,
                'width' => $width,
                'height' => $height,
                'pixel_stats' => [
                    'total_pixels' => $totalPixels,
                    'non_zero_pixels' => $nonZeroCount,
                    'non_zero_percentage' => round($nonZeroCount/$totalPixels*100, 2),
                    'min_brightness' => $minBrightness,
                    'max_brightness' => $maxBrightness,
                    'mean_brightness' => round($meanBrightness, 2)
                ],
                'sample_pixels' => $sampleValues,
                'message' => 'Изображение успешно загружено и конвертировано в grayscale'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в uploadImage: ' . $e->getMessage());
            Log::error('Трассировка: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Ошибка загрузки изображения: ' . $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    // Задание 3: 2D DCT изображения и создание спектра
    public function dct2D(Request $request)
    {
        try {
            $pixels = $request->input('pixels');
            $width = $request->input('width');
            $height = $request->input('height');

            if (!$pixels || !is_array($pixels) || !$width || !$height) {
                return response()->json(['error' => 'Неверный формат данных изображения'], 400);
            }

            $this->validateAndNormalizePixels($pixels, $width, $height);
            // Применяем 2D DCT к исходному изображению
            $dct2D = $this->apply2DDCT($pixels, $width, $height);

            // Создаем изображение спектра DCT (логарифмическая шкала)
            $spectrumPath = $this->createDctSpectrumImage($dct2D, $width, $height);

            // Анализ распределения энергии
            $energyAnalysis = $this->analyzeEnergyDistribution($dct2D, $width, $height);

            return response()->json([
                'success' => true,
                'dct2d' => $dct2D,
                'spectrum_path' => $spectrumPath ? Storage::url($spectrumPath) : null,
                'width' => $width,
                'height' => $height,
                'energy_analysis' => $energyAnalysis,
                'message' => '2D DCT успешно применен'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в dct2D: ' . $e->getMessage());
            Log::error('Трассировка: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Ошибка 2D DCT преобразования: ' . $e->getMessage()], 500);
        }
    }

// Применение 2D DCT
    private function apply2DDCT(array $pixels, int $width, int $height): array
    {
        $dct2D = [];

        // Сначала применяем DCT по строкам
        for ($y = 0; $y < $height; $y++) {
            $row = array_slice($pixels[$y], 0, $width);
            $dct2D[$y] = $this->apply1DDCT($row);
        }

        // Затем применяем DCT по столбцам
        for ($x = 0; $x < $width; $x++) {
            $column = [];
            for ($y = 0; $y < $height; $y++) {
                $column[$y] = $dct2D[$y][$x];
            }

            $colDCT = $this->apply1DDCT($column);

            for ($y = 0; $y < $height; $y++) {
                $dct2D[$y][$x] = $colDCT[$y];
            }
        }

        return $dct2D;
    }

// 1D DCT
    private function apply1DDCT(array $data): array
    {
        $N = count($data);
        $result = array_fill(0, $N, 0);

        for ($k = 0; $k < $N; $k++) {
            $sum = 0;
            $alpha = ($k == 0) ? sqrt(1 / $N) : sqrt(2 / $N);

            for ($n = 0; $n < $N; $n++) {
                $sum += $data[$n] * cos(pi() * $k * (2 * $n + 1) / (2 * $N));
            }

            $result[$k] = $alpha * $sum;
        }

        return $result;
    }

    // Анализ распределения энергии DCT коэффициентов (ИСПРАВЛЕННАЯ ВЕРСИЯ)
    private function analyzeEnergyDistribution(array $dctData, int $width, int $height): array
    {
        Log::info("=== НАЧАЛО АНАЛИЗА ЭНЕРГИИ DCT ===");
        Log::info("Размер изображения: {$width}x{$height} = " . ($width * $height) . " коэффициентов");

        $totalEnergy = 0;
        $coefficients = [];
        $allValues = [];

        // 1. Собираем все коэффициенты и вычисляем энергию
        Log::info("Сбор и вычисление энергии всех коэффициентов...");
        $minVal = INF;
        $maxVal = -INF;
        $dcValue = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $value = $dctData[$y][$x];
                $allValues[] = $value;

                // Находим мин/макс для понимания диапазона
                if ($value < $minVal) $minVal = $value;
                if ($value > $maxVal) $maxVal = $value;

                // DC коэффициент (первый)
                if ($x == 0 && $y == 0) {
                    $dcValue = $value;
                    Log::info("DC коэффициент: " . $dcValue);
                }

                // Вычисляем энергию (квадрат значения)
                $energy = $value * $value;

                $coefficients[] = [
                    'x' => $x,
                    'y' => $y,
                    'value' => $value,
                    'energy' => $energy,
                    'magnitude' => abs($value)
                ];

                $totalEnergy += $energy;
            }
        }

        Log::info("Диапазон значений коэффициентов: min=" . round($minVal, 4) . ", max=" . round($maxVal, 4));
        Log::info("Общая энергия: " . round($totalEnergy, 4));

        // 2. Если общая энергия близка к 0 - это ошибка
        if ($totalEnergy < 1e-10) {
            Log::warning("Общая энергия слишком мала: " . $totalEnergy);
            Log::warning("Проверьте входные данные и DCT преобразование");
            return [
                'total_energy' => 0,
                'total_coefficients' => count($coefficients),
                'energy_in_top_1_percent' => 0,
                'energy_in_top_5_percent' => 0,
                'energy_in_top_10_percent' => 0,
                'dc_percentage' => 0,
                'error' => 'Общая энергия слишком мала',
                'debug' => [
                    'min_value' => $minVal,
                    'max_value' => $maxVal,
                    'dc_value' => $dcValue
                ]
            ];
        }

        // 3. Доля энергии DC коэффициента
        $dcEnergy = $dcValue * $dcValue;
        $dcPercentage = ($dcEnergy / $totalEnergy) * 100;
        Log::info("Энергия DC коэффициента: " . round($dcEnergy, 4) . " (" . round($dcPercentage, 2) . "%)");

        // 4. Сортируем коэффициенты по убыванию энергии
        Log::info("Сортировка коэффициентов по энергии...");
        usort($coefficients, function($a, $b) {
            return $b['energy'] <=> $a['energy'];
        });

        $totalCoefficients = count($coefficients);
        Log::info("Всего коэффициентов: " . $totalCoefficients);

        // 5. Вычисляем энергию в топ-N процентах
        $percentages = [1, 5, 10, 20];
        $energyResults = [];

        foreach ($percentages as $percent) {
            $topCount = (int)ceil($totalCoefficients * ($percent / 100));
            $topEnergy = 0;

            for ($i = 0; $i < $topCount && $i < $totalCoefficients; $i++) {
                $topEnergy += $coefficients[$i]['energy'];
            }

            $energyPercentage = ($topEnergy / $totalEnergy) * 100;
            $energyResults["energy_in_top_{$percent}_percent"] = round($energyPercentage, 2);

            Log::info("Топ {$percent}% ({$topCount} коэф.): энергия = " .
                round($topEnergy, 4) . " (" . round($energyPercentage, 2) . "%)");

            // Детальный лог для 1%
            if ($percent == 1) {
                Log::info("--- Топ 1% коэффициентов (первые {$topCount}):");
                for ($i = 0; $i < min(5, $topCount); $i++) {
                    $coeff = $coefficients[$i];
                    Log::info("  [{$coeff['y']},{$coeff['x']}] значение: " .
                        round($coeff['value'], 4) .
                        ", энергия: " . round($coeff['energy'], 4) .
                        " (" . round(($coeff['energy'] / $totalEnergy) * 100, 3) . "%)");
                }
            }
        }

        // 6. Анализ распределения
        $quartileSize = (int)($totalCoefficients / 4);
        $quartileEnergy = [0, 0, 0, 0];
        for ($i = 0; $i < $totalCoefficients; $i++) {
            $quartileIndex = (int)($i / $quartileSize);
            if ($quartileIndex > 3) $quartileIndex = 3;
            $quartileEnergy[$quartileIndex] += $coefficients[$i]['energy'];
        }

        Log::info("Распределение энергии по квартилям:");
        for ($q = 0; $q < 4; $q++) {
            $percentage = ($quartileEnergy[$q] / $totalEnergy) * 100;
            Log::info("  Квартиль {$q}: " . round($percentage, 2) . "%");
        }

        // 7. Возвращаем результаты
        $result = [
            'total_energy' => round($totalEnergy, 4),
            'total_coefficients' => $totalCoefficients,
            'dc_coefficient' => round($dcValue, 4),
            'dc_energy' => round($dcEnergy, 4),
            'dc_percentage' => round($dcPercentage, 2),
            'min_coefficient' => round($minVal, 4),
            'max_coefficient' => round($maxVal, 4),
            'mean_coefficient' => round(array_sum($allValues) / count($allValues), 4),
            'std_coefficient' => $this->calculateStdDev($allValues),
        ];

        $result = array_merge($result, $energyResults);

        Log::info("=== ЗАВЕРШЕНИЕ АНАЛИЗА ЭНЕРГИИ DCT ===");

        return $result;
    }

// Вспомогательный метод для вычисления стандартного отклонения
    private function calculateStdDev(array $values): float
    {
        if (empty($values)) return 0;

        $mean = array_sum($values) / count($values);
        $sumSquares = 0;

        foreach ($values as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return round(sqrt($sumSquares / count($values)), 4);
    }

// Также исправьте метод createDctSpectrumImage():
    private function createDctSpectrumImage(array $dctData, int $width, int $height): ?string
    {
        try {
            Log::info("Создание изображения спектра DCT (логарифмическая шкала)...");

            $spectrumData = [];
            $maxLogVal = 0.0001;
            $minLogVal = INF;

            // Преобразуем DCT коэффициенты в логарифмическую шкалу
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $absVal = abs($dctData[$y][$x]);

                    // Логарифмическое преобразование (исправленное)
                    // Добавляем 1 для избежания log(0)
                    // Не умножаем на 1000, чтобы сохранить относительные величины
                    $logVal = log(1 + $absVal);
                    $spectrumData[$y][$x] = $logVal;

                    if ($logVal > $maxLogVal) $maxLogVal = $logVal;
                    if ($logVal < $minLogVal) $minLogVal = $logVal;
                }
            }

            Log::info("Логарифмический диапазон: min=" . round($minLogVal, 4) . ", max=" . round($maxLogVal, 4));

            // Создаем изображение спектра
            $image = $this->imageManager->create($width, $height);

            $range = $maxLogVal - $minLogVal;
            if ($range < 0.0001) $range = 1; // Избегаем деления на ноль

            Log::info("Нормализация в диапазон 0-255...");

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    // Нормализуем к диапазону 0-1
                    $normalized = ($spectrumData[$y][$x] - $minLogVal) / $range;
                    $intensity = (int)($normalized * 255);

                    // Ограничиваем диапазон
                    $intensity = max(0, min(255, $intensity));
                    $image->drawPixel($x, $y, [$intensity, $intensity, $intensity]);
                }
            }

            // Сохраняем изображение
            $filename = 'dct_spectrum_' . uniqid() . '.png';
            $path = 'public/lab6/' . $filename;
            $fullPath = storage_path('app/' . $path);

            $image->save($fullPath);
            Log::info("Спектр сохранен: " . $path);

            return $path;

        } catch (\Exception $e) {
            Log::error('Ошибка создания изображения спектра: ' . $e->getMessage());
            return null;
        }
    }

// ДОПОЛНИТЕЛЬНО: Добавьте этот метод для проверки входных данных
    private function validateAndNormalizePixels(array &$pixels, int $width, int $height): void
    {
        Log::info("Проверка и нормализация пикселей...");

        $min = INF;
        $max = -INF;
        $sum = 0;
        $count = 0;

        // Собираем статистику
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (isset($pixels[$y][$x])) {
                    $val = $pixels[$y][$x];
                    if ($val < $min) $min = $val;
                    if ($val > $max) $max = $val;
                    $sum += $val;
                    $count++;
                }
            }
        }

        Log::info("Статистика пикселей до DCT:");
        Log::info("  Диапазон: " . round($min, 2) . " - " . round($max, 2));
        Log::info("  Среднее: " . round($sum / max(1, $count), 2));

        // Если пиксели в диапазоне 0-255, нормализуем к 0-1 для DCT
        if ($max > 1.0 && $max <= 255.0) {
            Log::info("Нормализация из диапазона 0-255 в 0-1...");
            $normalizeFactor = 255.0;

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if (isset($pixels[$y][$x])) {
                        $pixels[$y][$x] = $pixels[$y][$x] / $normalizeFactor;
                    }
                }
            }

            // Пересчитываем статистику после нормализации
            $min = INF; $max = -INF; $sum = 0;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if (isset($pixels[$y][$x])) {
                        $val = $pixels[$y][$x];
                        if ($val < $min) $min = $val;
                        if ($val > $max) $max = $val;
                        $sum += $val;
                    }
                }
            }

            Log::info("Статистика пикселей после нормализации:");
            Log::info("  Диапазон: " . round($min, 4) . " - " . round($max, 4));
            Log::info("  Среднее: " . round($sum / max(1, $count), 4));
        }
    }

// Метод для получения только спектра DCT (без повторного вычисления)
    public function getDctSpectrum(Request $request)
    {
        try {
            $dctData = $request->input('dct_data');
            $width = $request->input('width');
            $height = $request->input('height');

            if (!$dctData || !is_array($dctData) || !$width || !$height) {
                return response()->json(['error' => 'Неверный формат DCT данных'], 400);
            }

            $spectrumPath = $this->createDctSpectrumImage($dctData, $width, $height);

            if (!$spectrumPath) {
                return response()->json(['error' => 'Не удалось создать спектр'], 500);
            }

            return response()->json([
                'success' => true,
                'spectrum_path' => Storage::url($spectrumPath),
                'message' => 'Спектр DCT создан'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в getDctSpectrum: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка создания спектра: ' . $e->getMessage()], 500);
        }
    }

// Метод для анализа энергии (можно вызвать отдельно)
    public function analyzeEnergy(Request $request)
    {
        try {
            $dctData = $request->input('dct_data');
            $width = $request->input('width');
            $height = $request->input('height');

            if (!$dctData || !is_array($dctData) || !$width || !$height) {
                return response()->json(['error' => 'Неверный формат DCT данных'], 400);
            }

            $energyAnalysis = $this->analyzeEnergyDistribution($dctData, $width, $height);

            return response()->json([
                'success' => true,
                'energy_analysis' => $energyAnalysis,
                'message' => 'Анализ распределения энергии выполнен'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в analyzeEnergy: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка анализа энергии: ' . $e->getMessage()], 500);
        }
    }

// Упрощенный 2D DCT
    private function applySimple2DDCT(array $pixels, int $width, int $height): array
    {
        $result = [];

        // Применяем DCT по строкам
        for ($y = 0; $y < $height; $y++) {
            $result[$y] = $this->apply1DDCT($pixels[$y]);
        }

        // Применяем DCT по столбцам
        for ($x = 0; $x < $width; $x++) {
            // Извлекаем столбец
            $column = [];
            for ($y = 0; $y < $height; $y++) {
                $column[$y] = $result[$y][$x];
            }

            // Применяем DCT к столбцу
            $colDCT = $this->apply1DDCT($column);

            // Записываем результат обратно
            for ($y = 0; $y < $height; $y++) {
                $result[$y][$x] = $colDCT[$y];
            }
        }

        return $result;
    }


    // Создание превью спектра DCT
    private function createDctSpectrumPreview(array $pixels, int $width, int $height, string $directory): ?string
    {
        try {
            // Ограничиваем размер для производительности
            $maxSize = 256;
            $scale = min($maxSize / $width, $maxSize / $height);
            $scaledWidth = (int)($width * $scale);
            $scaledHeight = (int)($height * $scale);

            // Масштабируем пиксели
            $scaledPixels = [];
            for ($y = 0; $y < $scaledHeight; $y++) {
                $row = [];
                for ($x = 0; $x < $scaledWidth; $x++) {
                    $origX = (int)($x / $scale);
                    $origY = (int)($y / $scale);
                    $row[$x] = $pixels[$origY][$origX] ?? 0;
                }
                $scaledPixels[] = $row;
            }

            // Применяем 2D DCT к уменьшенному изображению
            $dctResult = $this->dct2DArray($scaledPixels, $scaledWidth, $scaledHeight);

            // Создаем изображение спектра (логарифмическая шкала)
            $spectrumImage = $this->imageManager->create($scaledWidth, $scaledHeight);

            // Находим максимальное значение для нормализации
            $maxVal = 0;
            foreach ($dctResult as $row) {
                foreach ($row as $val) {
                    $absVal = abs($val);
                    if ($absVal > $maxVal) {
                        $maxVal = $absVal;
                    }
                }
            }

            if ($maxVal > 0) {
                for ($y = 0; $y < $scaledHeight; $y++) {
                    for ($x = 0; $x < $scaledWidth; $x++) {
                        $val = abs($dctResult[$y][$x]);
                        // Логарифмическая шкала
                        $logVal = log(1 + $val);
                        $logMax = log(1 + $maxVal);
                        $normalized = $logVal / $logMax;
                        $intensity = (int)($normalized * 255);

                        $spectrumImage->drawPixel($x, $y, [$intensity, $intensity, $intensity]);
                    }
                }
            }

            $spectrumFilename = 'spectrum_' . uniqid() . '.png';
            $spectrumPath = $directory . '/' . $spectrumFilename;
            $spectrumImage->save(storage_path('app/' . $spectrumPath));

            return $spectrumPath;

        } catch (\Exception $e) {
            Log::error('Ошибка создания спектра DCT: ' . $e->getMessage());
            return null;
        }
    }

    // 2D DCT для массива
    private function dct2DArray(array $pixels, int $width, int $height): array
    {
        $result = [];

        // Построчное DCT
        for ($y = 0; $y < $height; $y++) {
            $result[$y] = $this->dct1DArray($pixels[$y]);
        }

        // DCT по столбцам
        for ($x = 0; $x < $width; $x++) {
            $column = [];
            for ($y = 0; $y < $height; $y++) {
                $column[$y] = $result[$y][$x];
            }

            $colDCT = $this->dct1DArray($column);

            for ($y = 0; $y < $height; $y++) {
                $result[$y][$x] = $colDCT[$y];
            }
        }

        return $result;
    }

    // 1D DCT для массива
    private function dct1DArray(array $data): array
    {
        $N = count($data);
        $result = [];

        for ($k = 0; $k < $N; $k++) {
            $sum = 0;
            for ($n = 0; $n < $N; $n++) {
                $sum += $data[$n] * cos(pi() * $k * (2 * $n + 1) / (2 * $N));
            }
            $alpha = ($k == 0) ? sqrt(1 / $N) : sqrt(2 / $N);
            $result[$k] = $alpha * $sum;
        }

        return $result;
    }

    // В контроллер DctController добавьте:

    public function createDctSpectrum(Request $request)
    {
        try {
            $dctCoeffs = $request->input('pixels');
            $width = $request->input('width');
            $height = $request->input('height');

            if (!$dctCoeffs || !is_array($dctCoeffs)) {
                return response()->json(['error' => 'Неверный формат DCT коэффициентов'], 400);
            }

            // Создаем изображение спектра
            $spectrumPath = $this->createDctSpectrumImage($dctCoeffs, $width, $height);

            if (!$spectrumPath) {
                return response()->json(['error' => 'Не удалось создать спектр'], 500);
            }

            return response()->json([
                'success' => true,
                'spectrum_path' => Storage::url($spectrumPath),
                'message' => 'Спектр DCT создан'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в createDctSpectrum: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка создания спектра: ' . $e->getMessage()], 500);
        }
    }


    // Задание 4: блочное DCT 8x8 + квантование
    public function dct8x8JPEG(Request $request)
    {
        try {
            $pixels = $request->input('pixels');
            $strength = $request->input('strength', 2);

            if (!$pixels || !is_array($pixels)) {
                return response()->json(['error' => 'Неверный формат пикселей'], 400);
            }

            // Базовая таблица квантования (стандартная JPEG)
            $baseQuantizationTable = [
                [16, 11, 10, 16, 24, 40, 51, 61],
                [12, 12, 14, 19, 26, 58, 60, 55],
                [14, 13, 16, 24, 40, 57, 69, 56],
                [14, 17, 22, 29, 51, 87, 80, 62],
                [18, 22, 37, 56, 68, 109, 103, 77],
                [24, 35, 55, 64, 81, 104, 113, 92],
                [49, 64, 78, 87, 103, 121, 120, 101],
                [72, 92, 95, 98, 112, 100, 103, 99]
            ];

            // Применяем силу квантования
            $quantizationTable = [];
            $factor = $strength; // 1=низкое, 2=среднее, 3=высокое
            for ($i = 0; $i < 8; $i++) {
                for ($j = 0; $j < 8; $j++) {
                    $quantizationTable[$i][$j] = $baseQuantizationTable[$i][$j] * $factor;
                }
            }

            $height = count($pixels);
            $width = count($pixels[0]);
            $blocks = [];
            $compressedPixels = array_fill(0, $height, array_fill(0, $width, 0));

            // Обрабатываем блоки 8x8
            for ($y = 0; $y < $height; $y += 8) {
                for ($x = 0; $x < $width; $x += 8) {
                    $block = [];

                    // Извлекаем блок 8x8
                    for ($i = 0; $i < 8; $i++) {
                        for ($j = 0; $j < 8; $j++) {
                            $row = $y + $i;
                            $col = $x + $j;
                            $block[$i][$j] = $pixels[$row][$col] ?? 128; // Значение по умолчанию
                        }
                    }

                    // Применяем DCT
                    $dctBlock = $this->applyDCT8x8($block);

                    // Квантуем
                    $quantized = $this->quantizeBlock($dctBlock, $quantizationTable);

                    // Обратное квантование
                    $dequantized = $this->dequantizeBlock($quantized, $quantizationTable);

                    // Обратное DCT
                    $idctBlock = $this->applyIDCT8x8($dequantized);

                    // Восстанавливаем блок в изображение
                    for ($i = 0; $i < 8; $i++) {
                        for ($j = 0; $j < 8; $j++) {
                            $row = $y + $i;
                            $col = $x + $j;
                            if ($row < $height && $col < $width) {
                                // Ограничиваем значения 0-255
                                $value = $idctBlock[$i][$j];
                                $value = max(0, min(255, $value));
                                $compressedPixels[$row][$col] = (int)round($value);
                            }
                        }
                    }

                    $blocks[] = [
                        'x' => $x,
                        'y' => $y,
                        'quantized' => $quantized,
                    ];
                }
            }

            // Создаем сжатое изображение
            $compressedImage = $this->imageManager->create($width, $height);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $value = $compressedPixels[$y][$x];
                    $compressedImage->drawPixel($x, $y, [$value, $value, $value]);
                }
            }

            $filename = 'compressed_' . uniqid() . '.jpg';
            $compressedPath = 'public/lab6/' . $filename;
            $compressedImage->save(storage_path('app/' . $compressedPath), 90);

            // Вычисляем PSNR
            $psnr = $this->calculatePSNR($pixels, $compressedPixels, $height, $width);

            return response()->json([
                'success' => true,
                'blocks_count' => count($blocks),
                'compressed_path' => Storage::url($compressedPath),
                'psnr' => round($psnr, 2),
                'width' => $width,
                'height' => $height,
                'strength' => $strength,
                'message' => 'Сжатие JPEG выполнено успешно'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в dct8x8JPEG: ' . $e->getMessage());
            Log::error('Трассировка: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Ошибка блочного DCT: ' . $e->getMessage()], 500);
        }
    }

    private function applyDCT8x8($block)
    {
        $dctBlock = [];
        for ($u = 0; $u < 8; $u++) {
            for ($v = 0; $v < 8; $v++) {
                $sum = 0;
                for ($x = 0; $x < 8; $x++) {
                    for ($y = 0; $y < 8; $y++) {
                        $sum += $block[$x][$y] *
                            cos((2 * $x + 1) * $u * pi() / 16) *
                            cos((2 * $y + 1) * $v * pi() / 16);
                    }
                }
                $alphaU = ($u == 0) ? 1 / sqrt(2) : 1;
                $alphaV = ($v == 0) ? 1 / sqrt(2) : 1;
                $dctBlock[$u][$v] = 0.25 * $alphaU * $alphaV * $sum;
            }
        }
        return $dctBlock;
    }

    private function applyIDCT8x8($block)
    {
        $idctBlock = [];
        for ($x = 0; $x < 8; $x++) {
            for ($y = 0; $y < 8; $y++) {
                $sum = 0;
                for ($u = 0; $u < 8; $u++) {
                    for ($v = 0; $v < 8; $v++) {
                        $alphaU = ($u == 0) ? 1 / sqrt(2) : 1;
                        $alphaV = ($v == 0) ? 1 / sqrt(2) : 1;
                        $sum += $alphaU * $alphaV * $block[$u][$v] *
                            cos((2 * $x + 1) * $u * pi() / 16) *
                            cos((2 * $y + 1) * $v * pi() / 16);
                    }
                }
                $idctBlock[$x][$y] = 0.25 * $sum;
            }
        }
        return $idctBlock;
    }

    private function quantizeBlock($block, $quantTable)
    {
        $quantized = [];
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                $quantized[$i][$j] = round($block[$i][$j] / ($quantTable[$i][$j] ?: 1));
            }
        }
        return $quantized;
    }

    private function dequantizeBlock($block, $quantTable)
    {
        $dequantized = [];
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                $dequantized[$i][$j] = $block[$i][$j] * $quantTable[$i][$j];
            }
        }
        return $dequantized;
    }

    private function calculatePSNR($original, $compressed, $height, $width)
    {
        $mse = 0;
        $maxPixel = 255;
        $totalPixels = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (isset($original[$y][$x]) && isset($compressed[$y][$x])) {
                    $diff = $original[$y][$x] - $compressed[$y][$x];
                    $mse += $diff * $diff;
                    $totalPixels++;
                }
            }
        }

        if ($totalPixels == 0) {
            return 0;
        }

        $mse = $mse / $totalPixels;

        if ($mse == 0) {
            return INF;
        }

        $psnr = 10 * log10(($maxPixel * $maxPixel) / $mse);
        return $psnr;
    }
}
