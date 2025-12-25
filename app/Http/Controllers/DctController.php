<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DctController extends Controller
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function index()
    {
        return view('lab6.index');
    }

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

    public function uploadImage(Request $request)
    {
        try {
            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'Файл не загружен'], 400);
            }

            $file = $request->file('image');

            if (!$file->isValid()) {
                return response()->json(['error' => 'Неверный файл'], 400);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions)) {
                return response()->json(['error' => 'Неподдерживаемый формат изображения: ' . $extension], 400);
            }

            $directory = 'public/lab6';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            $filename = uniqid() . '_' . time() . '.' . $extension;
            $path = $directory . '/' . $filename;

            $file->move(storage_path('app/' . $directory), $filename);

            $fullPath = storage_path('app/' . $path);

            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'Файл не был сохранен'], 500);
            }

            $imageInfo = @getimagesize($fullPath);
            if (!$imageInfo) {
                return response()->json(['error' => 'Неверный формат изображения'], 400);
            }

            list($width, $height, $type) = $imageInfo;

            Log::info("Изображение: {$width}x{$height}, тип: {$type}");

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
                    $fileContent = file_get_contents($fullPath);
                    if ($fileContent !== false) {
                        $gdImage = @imagecreatefromstring($fileContent);
                    }
            }

            if (!$gdImage) {
                return response()->json(['error' => 'Не удалось загрузить изображение'], 500);
            }

            $grayImage = imagecreatetruecolor($width, $height);

            if ($type == IMAGETYPE_PNG) {
                imagealphablending($grayImage, false);
                imagesavealpha($grayImage, true);
                $transparent = imagecolorallocatealpha($grayImage, 0, 0, 0, 127);
                imagefill($grayImage, 0, 0, $transparent);
            }

            $pixels = [];
            $sampleValues = [];
            $totalBrightness = 0;
            $nonZeroCount = 0;
            $minBrightness = 255;
            $maxBrightness = 0;

            for ($y = 0; $y < $height; $y++) {
                $row = [];
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($gdImage, $x, $y);

                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $a = ($rgb >> 24) & 0x7F;

                    $brightness = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);

                    if ($type == IMAGETYPE_PNG && $a > 0) {
                        $brightness = (int)($brightness * (127 - $a) / 127);
                    }

                    $row[$x] = $brightness;

                    $totalBrightness += $brightness;
                    if ($brightness > 0) $nonZeroCount++;
                    if ($brightness < $minBrightness) $minBrightness = $brightness;
                    if ($brightness > $maxBrightness) $maxBrightness = $brightness;

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

            imagedestroy($gdImage);
            imagedestroy($grayImage);

            $totalPixels = $width * $height;
            $meanBrightness = $totalPixels > 0 ? $totalBrightness / $totalPixels : 0;

            Log::info("Статистика изображения:");
            Log::info("  Размер: {$width}x{$height} = {$totalPixels} пикселей");
            Log::info("  Ненулевых: {$nonZeroCount} (" . round($nonZeroCount/$totalPixels*100, 1) . "%)");
            Log::info("  Яркость: мин={$minBrightness}, макс={$maxBrightness}, среднее=" . round($meanBrightness, 2));
            Log::info("  Примеры пикселей:", $sampleValues);

            if ($nonZeroCount == 0) {
                Log::warning("Изображение полностью черное или все пиксели равны 0!");
            }

            $previewFilename = 'preview_' . $filename;
            $previewPath = $directory . '/' . $previewFilename;

            try {
                $previewImage = $this->imageManager->read($fullPath);
                $previewImage->scale(300, 300);
                $previewImage->save(storage_path('app/' . $previewPath));
            } catch (\Exception $e) {
                Log::warning("Не удалось создать превью через Intervention: " . $e->getMessage());
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
            $dct2D = $this->apply2DDCT($pixels, $width, $height);

            $spectrumPath = $this->createDctSpectrumImage($dct2D, $width, $height);

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

    private function apply2DDCT(array $pixels, int $width, int $height): array
    {
        $dct2D = [];

        for ($y = 0; $y < $height; $y++) {
            $row = array_slice($pixels[$y], 0, $width);
            $dct2D[$y] = $this->apply1DDCT($row);
        }

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

    private function analyzeEnergyDistribution(array $dctData, int $width, int $height): array
    {
        Log::info("=== НАЧАЛО АНАЛИЗА ЭНЕРГИИ DCT ===");
        Log::info("Размер изображения: {$width}x{$height} = " . ($width * $height) . " коэффициентов");

        $totalEnergy = 0;
        $coefficients = [];
        $allValues = [];

        Log::info("Сбор и вычисление энергии всех коэффициентов...");
        $minVal = INF;
        $maxVal = -INF;
        $dcValue = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $value = $dctData[$y][$x];
                $allValues[] = $value;

                if ($value < $minVal) $minVal = $value;
                if ($value > $maxVal) $maxVal = $value;

                // DC коэффициент (первый)
                if ($x == 0 && $y == 0) {
                    $dcValue = $value;
                    Log::info("DC коэффициент: " . $dcValue);
                }

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

        $dcEnergy = $dcValue * $dcValue;
        $dcPercentage = ($dcEnergy / $totalEnergy) * 100;
        Log::info("Энергия DC коэффициента: " . round($dcEnergy, 4) . " (" . round($dcPercentage, 2) . "%)");

        Log::info("Сортировка коэффициентов по энергии...");
        usort($coefficients, function($a, $b) {
            return $b['energy'] <=> $a['energy'];
        });

        $totalCoefficients = count($coefficients);
        Log::info("Всего коэффициентов: " . $totalCoefficients);

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

    // Создание изображения спектра DCT с цветовой шкалой и подписями
    private function createDctSpectrumImage(array $dctData, int $width, int $height): ?string
    {
        try {
            Log::info("Создание улучшенного спектра DCT с цветовой шкалой...");
            Log::info("Размер спектра: {$width}x{$height}");

            $padding = 80;
            $spectrumWidth = $width + $padding;
            $spectrumHeight = $height + $padding;

            // Создаем GD изображение
            $spectrumImage = imagecreatetruecolor($spectrumWidth, $spectrumHeight);

            if (!$spectrumImage) {
                Log::error("Не удалось создать GD изображение");
                return null;
            }

            $bgColor = imagecolorallocate($spectrumImage, 30, 30, 30); // Темно-серый фон
            $gridColor = imagecolorallocate($spectrumImage, 60, 60, 60); // Сетка
            $textColor = imagecolorallocate($spectrumImage, 200, 200, 200); // Текст
            $axisColor = imagecolorallocate($spectrumImage, 100, 100, 200); // Оси

            imagefill($spectrumImage, 0, 0, $bgColor);

            $logData = [];
            $maxLogVal = 0.0001;
            $minLogVal = PHP_FLOAT_MAX;

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $absVal = abs($dctData[$y][$x]);

                    $logVal = log(1 + $absVal * 100);
                    $logData[$y][$x] = $logVal;

                    if ($logVal > $maxLogVal) $maxLogVal = $logVal;
                    if ($logVal < $minLogVal) $minLogVal = $logVal;
                }
            }

            Log::info("Логарифмический диапазон: min=" . round($minLogVal, 4) . ", max=" . round($maxLogVal, 4));

            $colorMap = $this->createJetColorMap(256);

            $range = $maxLogVal - $minLogVal;
            if ($range < 0.0001) {
                Log::warning("Диапазон слишком мал, использую range=1");
                $range = 1;
            }

            $spectrumX = 40;
            $spectrumY = 40;

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $normalized = ($logData[$y][$x] - $minLogVal) / $range;
                    $normalized = max(0, min(1, $normalized));

                    $colorIndex = (int)($normalized * 255);
                    $color = $colorMap[$colorIndex];

                    $pixelColor = imagecolorallocate($spectrumImage, $color[0], $color[1], $color[2]);
                    imagesetpixel($spectrumImage, $spectrumX + $x, $spectrumY + $y, $pixelColor);

                    imagecolordeallocate($spectrumImage, $pixelColor);
                }
            }

            $this->addColorScale($spectrumImage, $colorMap, $spectrumWidth - 30, $spectrumY, 20, $height, $minLogVal, $maxLogVal, $textColor);

            imagerectangle($spectrumImage, $spectrumX - 1, $spectrumY - 1,
                $spectrumX + $width, $spectrumY + $height, $axisColor);

            $dcColor = imagecolorallocate($spectrumImage, 255, 255, 0); // Желтый
            imagerectangle($spectrumImage, $spectrumX - 2, $spectrumY - 2,
                $spectrumX + 2, $spectrumY + 2, $dcColor);

            for ($i = 32; $i < $height; $i += 32) {
                imageline($spectrumImage, $spectrumX, $spectrumY + $i,
                    $spectrumX + $width, $spectrumY + $i, $gridColor);
            }

            for ($i = 32; $i < $width; $i += 32) {
                imageline($spectrumImage, $spectrumX + $i, $spectrumY,
                    $spectrumX + $i, $spectrumY + $height, $gridColor);
            }

            $fontSize = 4;
            $titleY = 15;
            $footerY = $spectrumHeight - 25;

            $this->addTextUTF8($spectrumImage, $textColor, $fontSize, $spectrumX, $spectrumY - 25, "Низкие частоты →");
            $this->addTextUTF8($spectrumImage, $textColor, $fontSize, $spectrumX + $width - 120, $spectrumY + $height + 15, "← Высокие частоты");

            $infoText = sprintf("Спектр DCT | Размер: %dx%d коэффициентов", $width, $height);
            $this->addTextUTF8($spectrumImage, $textColor, 3, 10, $footerY, $infoText);

            $legendY = $footerY - 20;
            $this->addTextUTF8($spectrumImage, $textColor, 3, 10, $legendY, "Темные = малые значения, Яркие = большие значения");

            $filename = 'dct_spectrum_color_' . uniqid() . '.png';
            $path = 'public/lab6/' . $filename;
            $fullPath = storage_path('app/' . $path);

            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Сохраняем PNG с высокой четкостью
            if (imagepng($spectrumImage, $fullPath, 9)) {
                $fileSize = filesize($fullPath);
                Log::info("Цветной спектр сохранен: {$path} ({$fileSize} bytes)");
            } else {
                Log::error("Не удалось сохранить спектр: {$fullPath}");
                return null;
            }

            imagedestroy($spectrumImage);

            return $path;

        } catch (\Exception $e) {
            Log::error('Ошибка создания цветного спектра: ' . $e->getMessage());
            Log::error('Трассировка: ' . $e->getTraceAsString());
            return null;
        }
    }

    private function addTextUTF8($image, $color, $fontSize, $x, $y, $text): void
    {
        if (is_string($fontSize) && file_exists($fontSize)) {
            $font = $fontSize;
            $fontSizeNum = 12;

            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            imagettftext($image, $fontSizeNum, 0, $x, $y, $color, $font, $text);
        } else {
            $text = $this->transliterateIfNeeded($text);
            imagestring($image, (int)$fontSize, $x, $y, $text, $color);
        }
    }

//  метод для транслитерации
    private function transliterateIfNeeded(string $text): string
    {
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        return strtr($text, $translit);
    }

// Создание цветовой палитры Jet (синий-зеленый-красный)
    private function createJetColorMap(int $size): array
    {
        $colorMap = [];

        for ($i = 0; $i < $size; $i++) {
            $pos = $i / ($size - 1);

            if ($pos < 0.125) {
                // От черного к синему
                $r = 0;
                $g = 0;
                $b = (int)(127 + 128 * $pos / 0.125);
            } elseif ($pos < 0.375) {
                // От синего к голубому
                $r = 0;
                $g = (int)(255 * ($pos - 0.125) / 0.25);
                $b = 255;
            } elseif ($pos < 0.625) {
                // От голубого к желтому
                $r = (int)(255 * ($pos - 0.375) / 0.25);
                $g = 255;
                $b = (int)(255 * (0.625 - $pos) / 0.25);
            } elseif ($pos < 0.875) {
                // От желтого к красному
                $r = 255;
                $g = (int)(255 * (0.875 - $pos) / 0.25);
                $b = 0;
            } else {
                // От красного к темно-красному
                $r = (int)(255 * (1 - ($pos - 0.875) / 0.125));
                $g = 0;
                $b = 0;
            }

            $colorMap[$i] = [$r, $g, $b];
        }

        return $colorMap;
    }

    private function addColorScale($image, array $colorMap, int $x, int $y, int $width, int $height,
                                   float $minVal, float $maxVal, int $textColor): void
    {
        $scaleHeight = $height;
        $scaleWidth = $width;

        for ($i = 0; $i < $scaleHeight; $i++) {
            $normalized = 1 - ($i / $scaleHeight); // Инвертируем (высокие значения вверху)
            $colorIndex = (int)($normalized * 255);
            $color = $colorMap[$colorIndex];

            $lineColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
            imageline($image, $x, $y + $i, $x + $scaleWidth - 1, $y + $i, $lineColor);
            imagecolordeallocate($image, $lineColor);
        }

        imagerectangle($image, $x - 1, $y - 1, $x + $scaleWidth, $y + $scaleHeight, $textColor);

        // Подписи шкалы
        $font = 2;
        $step = $scaleHeight / 5;

        for ($i = 0; $i <= 5; $i++) {
            $posY = $y + $i * $step;
            $value = $minVal + ($maxVal - $minVal) * (1 - $i/5);

            // Линия деления
            imageline($image, $x + $scaleWidth, $posY, $x + $scaleWidth + 5, $posY, $textColor);

            // Подпись значения
            $label = sprintf("%.2f", $value);
            imagestring($image, $font, $x + $scaleWidth + 8, $posY - 5, $label, $textColor);
        }

        // Заголовок шкалы
        imagestringup($image, 3, $x + $scaleWidth + 25, $y + $scaleHeight/2, 'log(1 + |coeff|)', $textColor);
    }

    private function addSpectrumInfo($image, int $width, int $height, float $minVal, float $maxVal,
                                     int $textColor, int $spectrumWidth, int $spectrumHeight): void
    {
        $infoY = $height - 30;

        // Заголовок
        $title = "SPECTR DCT (log scale)"; // Используем английский или транслитерацию
        $titleWidth = imagefontwidth(4) * strlen($title);
        imagestring($image, 4, ($width - $titleWidth) / 2, 10, $title, $textColor);

        // Информация
        $infoText = sprintf("Size: %dx%d | Range: %.2f - %.2f",
            $spectrumWidth, $spectrumHeight, $minVal, $maxVal);
        imagestring($image, 3, 10, $infoY, $infoText, $textColor);

        // Легенда
        $legendY = $height - 60;
        imagestring($image, 3, 10, $legendY, "Dark = low values, Bright = high values", $textColor);
    }

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

    private function applySimple2DDCT(array $pixels, int $width, int $height): array
    {
        $result = [];

        for ($y = 0; $y < $height; $y++) {
            $result[$y] = $this->apply1DDCT($pixels[$y]);
        }

        for ($x = 0; $x < $width; $x++) {
            $column = [];
            for ($y = 0; $y < $height; $y++) {
                $column[$y] = $result[$y][$x];
            }

            $colDCT = $this->apply1DDCT($column);

            for ($y = 0; $y < $height; $y++) {
                $result[$y][$x] = $colDCT[$y];
            }
        }

        return $result;
    }

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

            Log::info("Начало блочного DCT 8x8. Размер изображения: " . count($pixels) . "x" . count($pixels[0]));

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

            Log::info("Изображение {$width}x{$height}. Обработка блоков 8x8...");

            // Обрабатываем блоки 8x8
            $blockCount = 0;
            for ($y = 0; $y < $height; $y += 8) {
                for ($x = 0; $x < $width; $x += 8) {
                    $block = [];

                    // Извлекаем блок 8x8
                    for ($i = 0; $i < 8; $i++) {
                        for ($j = 0; $j < 8; $j++) {
                            $row = $y + $i;
                            $col = $x + $j;

                            // Проверяем границы изображения
                            if ($row < $height && $col < $width) {
                                $block[$i][$j] = $pixels[$row][$col] ?? 128;
                            } else {
                                // Заполняем паддингом для неполных блоков
                                $block[$i][$j] = 128;
                            }
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

                    $blockCount++;
                }
            }

            Log::info("Обработано блоков: {$blockCount}");

            // ========== СОЗДАЕМ СЖАТОЕ ИЗОБРАЖЕНИЕ (ИСПРАВЛЕННЫЙ СПОСОБ) ==========

            // Создаем новое GD изображение
            $gdImage = imagecreatetruecolor($width, $height);

            if (!$gdImage) {
                throw new \Exception("Не удалось создать GD изображение");
            }

            // Заполняем изображение пикселями
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $value = $compressedPixels[$y][$x];
                    $color = imagecolorallocate($gdImage, $value, $value, $value);
                    imagesetpixel($gdImage, $x, $y, $color);
                    imagecolordeallocate($gdImage, $color);
                }
            }

            // Сохраняем сжатое изображение
            $filename = 'compressed_' . uniqid() . '.jpg';
            $compressedPath = 'public/lab6/' . $filename;
            $fullPath = storage_path('app/' . $compressedPath);

            // Создаем директорию если нужно
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Сохраняем как JPEG
            if (!imagejpeg($gdImage, $fullPath, 90)) {
                throw new \Exception("Не удалось сохранить сжатое изображение");
            }

            // Очищаем память
            imagedestroy($gdImage);

            // Вычисляем PSNR
            $psnr = $this->calculatePSNR($pixels, $compressedPixels, $height, $width);

            // Создаем превью через Intervention для согласованности
            try {
                $previewImage = $this->imageManager->read($fullPath);
                $previewImage->scale(300, 300);

                $previewFilename = 'preview_' . $filename;
                $previewPath = 'public/lab6/' . $previewFilename;
                $previewImage->save(storage_path('app/' . $previewPath));

                $previewUrl = Storage::url($previewPath);
            } catch (\Exception $e) {
                Log::warning("Не удалось создать превью через Intervention: " . $e->getMessage());
                $previewUrl = Storage::url($compressedPath);
            }

            return response()->json([
                'success' => true,
                'blocks_count' => $blockCount,
                'compressed_path' => Storage::url($compressedPath),
                'preview_path' => $previewUrl,
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
