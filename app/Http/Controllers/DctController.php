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
    // Задание 3: загрузка изображения
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

            // Сохраняем оригинальный файл ПРОСТО через move
            $file->move(storage_path('app/' . $directory), $filename);

            // Загружаем изображение через ImageManager - используем содержимое файла
            $fullPath = storage_path('app/' . $path);

            // Проверяем, что файл существует
            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'Файл не был сохранен'], 500);
            }

            // Читаем файловое содержимое
            $fileContent = file_get_contents($fullPath);

            // Создаем изображение из содержимого
            $image = $this->imageManager->read($fileContent);

            // Конвертируем в оттенки серого
            $image = $image->greyscale();

            $width = $image->width();
            $height = $image->height();

            // Получаем пиксели (упрощенный метод - берем первый канал)
            $pixels = [];
            for ($y = 0; $y < $height; $y++) {
                $row = [];
                for ($x = 0; $x < $width; $x++) {
                    // Получаем значение пикселя
                    $color = $image->pickColor($x, $y);
                    $row[$x] = is_array($color) ? $color[0] : 0; // Яркость (первый канал)
                }
                $pixels[$y] = $row;
            }

            // Сохраняем уменьшенную версию для превью
            $previewFilename = 'preview_' . $filename;
            $previewPath = $directory . '/' . $previewFilename;

            // Создаем копию для превью и масштабируем
            $previewImage = $image->scale(300, 300);
            $previewImage->save(storage_path('app/' . $previewPath));

            return response()->json([
                'success' => true,
                'path' => Storage::url($previewPath),
                'original_path' => Storage::url($path),
                'pixels' => $pixels,
                'width' => $width,
                'height' => $height,
                'message' => 'Изображение успешно загружено'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в uploadImage: ' . $e->getMessage());
            Log::error('Трассировка: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Ошибка загрузки изображения: ' . $e->getMessage()
            ], 500);
        }
    }

// 2D DCT API метод (упрощенный)
    public function dct2D(Request $request)
    {
        try {
            $pixels = $request->input('pixels');

            if (!$pixels || !is_array($pixels)) {
                return response()->json(['error' => 'Неверный формат пикселей'], 400);
            }

            $height = count($pixels);
            if ($height === 0) {
                return response()->json(['error' => 'Пустой массив пикселей'], 400);
            }

            $width = count($pixels[0]);

            // Применяем 2D DCT (упрощенная версия)
            $dct2D = $this->applySimple2DDCT($pixels, $width, $height);

            return response()->json([
                'success' => true,
                'dct2d' => $dct2D,
                'width' => $width,
                'height' => $height,
                'message' => '2D DCT успешно применен'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка в dct2D: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка 2D DCT преобразования: ' . $e->getMessage()], 500);
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

// 1D DCT
    private function apply1DDCT(array $data): array
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


    // Создание изображения спектра DCT
    private function createDctSpectrumImage(array $dctData, int $width, int $height): ?string
    {
        try {
            // Находим максимальное абсолютное значение для нормализации
            $maxVal = 0;
            foreach ($dctData as $row) {
                foreach ($row as $val) {
                    $absVal = abs($val);
                    if ($absVal > $maxVal) {
                        $maxVal = $absVal;
                    }
                }
            }

            if ($maxVal == 0) {
                return null;
            }

            // Создаем изображение
            $image = $this->imageManager->create($width, $height);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $val = abs($dctData[$y][$x]);
                    // Логарифмическая шкала для лучшей визуализации
                    $logVal = log(1 + $val);
                    $logMax = log(1 + $maxVal);
                    $normalized = $logVal / $logMax;
                    $intensity = (int)($normalized * 255);

                    // Заливаем пиксель
                    $image->drawPixel($x, $y, [$intensity, $intensity, $intensity]);
                }
            }

            $filename = 'dct_spectrum_' . uniqid() . '.png';
            $path = 'public/lab6/' . $filename;
            $image->save(storage_path('app/' . $path));

            return $path;

        } catch (\Exception $e) {
            Log::error('Ошибка создания изображения спектра: ' . $e->getMessage());
            return null;
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

    // Метод для получения спектра DCT
    public function getDctSpectrum(Request $request)
    {
        try {
            $pixels = $request->input('pixels');
            $width = $request->input('width');
            $height = $request->input('height');

            if (!$pixels || !is_array($pixels)) {
                return response()->json(['error' => 'Неверный формат пикселей'], 400);
            }

            $spectrumPath = $this->createDctSpectrumImage($pixels, $width, $height);

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
}
