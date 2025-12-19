<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Colors\Rgb\Color;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImageFilterController extends Controller
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function index()
    {
        return view('lab5.index');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:5120',
            ]);

            if ($request->hasFile('image')) {
                Storage::disk('public')->deleteDirectory('lab5');

                Storage::disk('public')->makeDirectory('lab5/original');
                Storage::disk('public')->makeDirectory('lab5/preview');
                Storage::disk('public')->makeDirectory('lab5/noised');
                Storage::disk('public')->makeDirectory('lab5/filtered');

                $image = $request->file('image');
                $filename = 'current_image.' . $image->getClientOriginalExtension();

                $path = $image->storeAs('lab5/original', $filename, 'public');

                $img = $this->imageManager->read($image->getRealPath());
                $img->scaleDown(600, 450);
                $previewPath = 'lab5/preview/' . $filename;
                Storage::disk('public')->put($previewPath, $img->encode(new JpegEncoder(90)));

                return response()->json([
                    'success' => true,
                    'message' => 'Изображение успешно загружено',
                    'original_path' => Storage::url($path),
                    'preview_path' => Storage::url($previewPath),
                    'filename' => $filename
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке изображения: ' . $e->getMessage()
            ], 500);
        }
    }

    public function applyNoise(Request $request): JsonResponse
    {
        try {
            set_time_limit(60);

            $request->validate([
                'noise_type' => 'required|string|in:gaussian,white,perlin,voronoi,curl',
                'intensity' => 'required|numeric|min:0|max:100',
            ]);

            $noiseType = $request->input('noise_type');
            $intensity = (int)$request->input('intensity');

            Log::info('$intensity', (array) $intensity);
            $originalPath = Storage::disk('public')->path('lab5/original/current_image.*');
            $files = glob($originalPath);

            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Исходное изображение не найдено. Пожалуйста, сначала загрузите изображение.'
                ], 404);
            }

            $originalPath = $files[0];

            $img = $this->imageManager->read($originalPath);

            $img->scaleDown(600, 450);

            switch ($noiseType) {
                case 'gaussian':
                    $this->applyGaussianNoise($img, $intensity);
                    break;
                case 'white':
                    $this->applyWhiteNoise($img, $intensity);
                    break;
                case 'voronoi':
                    $this->applyVoronoiNoise($img, $intensity);
                    break;
                case 'perlin':
                    $this->applyPerlinNoise($img, $intensity);
                    break;
//                case 'curl':
//                    $this->applyCurlNoise($img, $intensity);
//                    break;
            }

            $noisedFilename = 'current_noised.jpg';
            $noisedPath = 'lab5/noised/' . $noisedFilename;
            Storage::disk('public')->put($noisedPath, $img->encode(new JpegEncoder(90)));

            return response()->json([
                'success' => true,
                'message' => 'Шум успешно применен',
                'image_path' => Storage::url($noisedPath),
                'filename' => $noisedFilename
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при наложении шума: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Применение фильтра к изображению
     */
    public function applyFilter(Request $request): JsonResponse
    {
        try {
            set_time_limit(90);

            $request->validate([
                'filter_type' => 'required|string|in:lowpass,highpass,median',
                'mask_type' => 'required|string|in:h1,h2,h3,h4,h5,h6,h7,h8,h9,custom',
                'mask_size' => 'required|integer|min:3|max:5',
                'brightness_factor' => 'nullable|numeric|min:0|max:10',
            ]);

            $filterType = $request->input('filter_type');
            $maskType = $request->input('mask_type');
            $maskSize = min((int)$request->input('mask_size'), 5);
            $brightnessFactor = $request->input('brightness_factor', 1);

            $noisedPath = Storage::disk('public')->path('lab5/noised/current_noised.jpg');

            if (!file_exists($noisedPath)) {
                // Если файл с шумом не найден, используем оригинальное
                $originalPath = Storage::disk('public')->path('lab5/original/current_image.*');
                $files = glob($originalPath);

                if (empty($files)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Изображение не найдено. Пожалуйста, сначала загрузите изображение.'
                    ], 404);
                }

                $noisedPath = $files[0];
            }

            $img = $this->imageManager->read($noisedPath);

            // Уменьшаем изображение перед фильтрацией
            $img->scaleDown(600, 450);

            switch ($filterType) {
                case 'lowpass':
                    $this->applyLowPassFilterOptimized($img, $maskType, $maskSize);
                    break;
                case 'highpass':
                    $this->applyHighPassFilterOptimized($img, $maskType, $maskSize, $brightnessFactor);
                    break;
                case 'median':
                    $this->applyMedianFilterOptimized($img, $maskSize);
                    break;
            }

            $filteredFilename = 'current_filtered.jpg';
            $filteredPath = 'lab5/filtered/' . $filteredFilename;
            Storage::disk('public')->put($filteredPath, $img->encode(new JpegEncoder(90)));

            return response()->json([
                'success' => true,
                'message' => 'Фильтр успешно применен',
                'image_path' => Storage::url($filteredPath),
                'filename' => $filteredFilename
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при применении фильтра: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 1. Гауссов шум
     */
    private function applyGaussianNoise($image, $sigma): void
    {
        $width = $image->width();
        $height = $image->height();

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = $image->pickColor($x, $y);

                $noiseR = $this->gaussianRandom(0, $sigma);
                $noiseG = $this->gaussianRandom(0, $sigma);
                $noiseB = $this->gaussianRandom(0, $sigma);

                $r = (int)max(0, min(255, $color->red()->value() + $noiseR));
                $g = (int)max(0, min(255, $color->green()->value() + $noiseG));
                $b = (int)max(0, min(255, $color->blue()->value() + $noiseB));

                $image->drawPixel($x, $y, new Color($r, $g, $b));
            }
        }
    }

    private function gaussianRandom($mean, $std): float
    {
        // Бокс-Мюллер преобразование
        $u1 = rand(0, PHP_INT_MAX) / PHP_INT_MAX;
        $u2 = rand(0, PHP_INT_MAX) / PHP_INT_MAX;

        //Преобразование Бокса-Мюллера
        $z0 = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        return $mean + $z0 * $std;
    }

    /**
     * Белый шум
     */
    private function applyWhiteNoise($image, $intensity): void
    {
        $width = $image->width();
        $height = $image->height();

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (rand(0, 100) < $intensity / 2) {
                    $noise = rand(0, 255);
                    $image->drawPixel($x, $y, new Color($noise, $noise, $noise));
                }
            }
        }
    }

    /**
     * Шум Перлина
     */
    private function applyPerlinNoise($image, $intensity): void
    {
        $width = $image->width();
        $height = $image->height();

        $scale = 0.05;  // Масштаб паттерна
        $octaves = 4;   // Количество октав
        $persistence = 0.5; // Затухание амплитуды

        // Предвычисляем карту шума для оптимизации
        $noiseMap = [];

        for ($y = 0; $y < $height; $y++) {
            $noiseMap[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                // Вычисляем значение шума Перлина
                $noiseValue = $this->improvedPerlin(
                    $x * $scale,
                    $y * $scale,
                    $octaves,
                    $persistence
                );

                // Преобразуем из диапазона [0,1] в значение шума с интенсивностью
                // Шум может быть как положительным, так и отрицательным
                $noiseValue = ($noiseValue - 0.5) * 2; // [-1, 1]

                $noiseMap[$y][$x] = (int)(
                    $noiseValue * 127.5 * ($intensity / 100)
                );
            }
        }

        // Применяем шум к изображению
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = $image->pickColor($x, $y);
                $noise = $noiseMap[$y][$x];

                // Добавляем шум к каждому каналу
                $r = max(0, min(255, (int)$color->red()->value() + $noise));
                $g = max(0, min(255, (int)$color->green()->value() + $noise));
                $b = max(0, min(255, (int)$color->blue()->value() + $noise));

                $image->drawPixel($x, $y, new Color($r, $g, $b));
            }
        }
    }

    /**
     * Таблица перестановок для шума Перлина (256 случайных значений)
     */
    private array $perm = [];

    /**
     * Инициализация таблицы перестановок
     */
    private function initPermutationTable(): void
    {
        if (!empty($this->perm)) {
            return;
        }

        // Создаём массив 0..255
        $p = range(0, 255);

        // Перемешиваем
        shuffle($p);

        // Дублируем для удобства доступа (512 элементов)
        $this->perm = array_merge($p, $p);
    }

    /**
     * Линейная интерполяция
     */
    private function lerp(float $a, float $b, float $t): float
    {
        return $a + $t * ($b - $a);
    }

    /**
     * Функция затухания (fade) 6t^5 - 15t^4 + 10t^3
     */
    private function fade(float $t): float
    {
        return $t * $t * $t * ($t * ($t * 6 - 15) + 10);
    }

    /**
     * Вычисление градиента
     */
    private function grad(int $hash, float $x, float $y): float
    {
        // Берём последние 4 бита хэша
        $h = $hash & 15;

        // Преобразуем в градиент
        $u = $h < 8 ? $x : $y;
        $v = $h < 4 ? $y : ($h == 12 || $h == 14 ? $x : 0);

        return (($h & 1) == 0 ? $u : -$u) + (($h & 2) == 0 ? $v : -$v);
    }

    /**
     * Улучшенный 2D шум Перлина
     */
    private function perlin2D(float $x, float $y): float
    {
        // Инициализируем таблицу перестановок при первом вызове
        if (empty($this->perm)) {
            $this->initPermutationTable();
        }

        // Находим целые координаты
        $xi = (int)floor($x) & 255;
        $yi = (int)floor($y) & 255;

        // Дробные части
        $xf = $x - floor($x);
        $yf = $y - floor($y);

        // Фейдинг-функция
        $u = $this->fade($xf);
        $v = $this->fade($yf);

        // Хэши для 4 углов
        $aa = $this->perm[$this->perm[$xi] + $yi];
        $ab = $this->perm[$this->perm[$xi] + $yi + 1];
        $ba = $this->perm[$this->perm[$xi + 1] + $yi];
        $bb = $this->perm[$this->perm[$xi + 1] + $yi + 1];

        // Интерполяция
        $x1 = $this->lerp(
            $this->grad($aa, $xf, $yf),
            $this->grad($ba, $xf - 1, $yf),
            $u
        );

        $x2 = $this->lerp(
            $this->grad($ab, $xf, $yf - 1),
            $this->grad($bb, $xf - 1, $yf - 1),
            $u
        );

        // Возвращаем нормализованное значение
        return ($this->lerp($x1, $x2, $v) + 1) / 2;
    }

    /**
     * Фрактальный шум Перлина (несколько октав)
     */
    private function improvedPerlin(float $x, float $y, int $octaves = 4, float $persistence = 0.5): float
    {
        $total = 0.0;
        $frequency = 1.0;
        $amplitude = 1.0;
        $maxValue = 0.0;

        for ($i = 0; $i < $octaves; $i++) {
            $total += $this->perlin2D($x * $frequency, $y * $frequency) * $amplitude;
            $maxValue += $amplitude;
            $amplitude *= $persistence;
            $frequency *= 2.0;
        }

        return $total / $maxValue;
    }

    /**
     * 2. Шум Вороного
     */
    private function applyVoronoiNoise($image, $intensity): void
    {
        $width = $image->width();
        $height = $image->height();

        $cellSize = max(5, 100 - $intensity);
        $numPointsX = ceil($width / $cellSize);
        $numPointsY = ceil($height / $cellSize);
        $numPoints = $numPointsX * $numPointsY;

        // Создаём более равномерное распределение
        $points = [];
        for ($i = 0; $i < $numPoints; $i++) {
            $cellX = ($i % $numPointsX);
            $cellY = floor($i / $numPointsX);

            // Случайное смещение внутри ячейки джиттер
            $points[] = [
                'x' => $cellX * $cellSize + rand(0, $cellSize - 1),
                'y' => $cellY * $cellSize + rand(0, $cellSize - 1)
            ];
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $minDist = PHP_INT_MAX;

                // Проверяем только ближайшие точки (оптимизация)
                $startX = max(0, floor($x / $cellSize) - 1);
                $endX = min($numPointsX - 1, ceil($x / $cellSize) + 1);
                $startY = max(0, floor($y / $cellSize) - 1);
                $endY = min($numPointsY - 1, ceil($y / $cellSize) + 1);

                for ($cy = $startY; $cy <= $endY; $cy++) {
                    for ($cx = $startX; $cx <= $endX; $cx++) {
                        $index = $cy * $numPointsX + $cx;
                        if ($index < count($points)) {
                            $point = $points[$index];
                            $dx = $x - $point['x'];
                            $dy = $y - $point['y'];
                            $dist = $dx * $dx + $dy * $dy;

                            if ($dist < $minDist) {
                                $minDist = $dist;
                            }
                        }
                    }
                }

                $noiseValue = (int)(sqrt($minDist) * ($intensity / 20));

                $noiseValue = min(100, $noiseValue);

                $color = $image->pickColor($x, $y);

                $r = max(0, min(255, (int)$color->red()->value() + $noiseValue));
                $g = max(0, min(255, (int)$color->green()->value() + $noiseValue));
                $b = max(0, min(255, (int)$color->blue()->value() + $noiseValue));

                $image->drawPixel($x, $y, new Color($r, $g, $b));
            }
        }
    }

    /**
     * Вихревой шум
     */
    private function applyCurlNoise($image, $intensity): void
    {
        $width = $image->width();
        $height = $image->height();

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                $color = $image->pickColor($x, $y);

                $angle = atan2($y - $height/2, $x - $width/2);
                $radius = sqrt(pow($x - $width/2, 2) + pow($y - $height/2, 2));

                $noise = sin($angle * 3 + $radius * 0.05) * $intensity;

                $r = max(0, min(255, (int)$color->red()->value() + $noise));
                $g = max(0, min(255, (int)$color->green()->value() + $noise));
                $b = max(0, min(255, (int)$color->blue()->value() + $noise));

                $newColor = new Color($r, $g, $b);
                $image->drawPixel($x, $y, $newColor);

                if ($x + 1 < $width) {
                    $image->drawPixel($x + 1, $y, $newColor);
                }
                if ($y + 1 < $height) {
                    $image->drawPixel($x, $y + 1, $newColor);
                }
            }
        }
    }

    /**
     * Функция для упрощенного шума Перлина
     */
    private function perlin($x, $y, $persistence): float
    {
        $total = 0;
        $frequency = 1;
        $amplitude = 1;
        $maxValue = 0;

        for ($i = 0; $i < 3; $i++) {
            $total += $this->interpolatedNoise($x * $frequency, $y * $frequency) * $amplitude;
            $maxValue += $amplitude;
            $amplitude *= $persistence;
            $frequency *= 2;
        }

        return $total / $maxValue;
    }

    private function interpolatedNoise($x, $y): float
    {
        $xi = (int)$x;
        $yi = (int)$y;
        $xf = $x - $xi;
        $yf = $y - $yi;

        $v1 = $this->smoothNoise($xi, $yi);
        $v2 = $this->smoothNoise($xi + 1, $yi);
        $v3 = $this->smoothNoise($xi, $yi + 1);
        $v4 = $this->smoothNoise($xi + 1, $yi + 1);

        $i1 = $this->interpolate($v1, $v2, $xf);
        $i2 = $this->interpolate($v3, $v4, $xf);

        return $this->interpolate($i1, $i2, $yf);
    }

    private function smoothNoise($x, $y): float
    {
        $n = (sin($x * 12.9898 + $y * 78.233) * 43758.5453);
        return $n - floor($n);
    }

    private function interpolate($a, $b, $x): float
    {
        $ft = $x * M_PI;
        $f = (1 - cos($ft)) * 0.5;
        return $a * (1 - $f) + $b * $f;
    }

    /**
     * Низкочастотная фильтрация
     */
    private function applyLowPassFilterOptimized($image, string $maskType, int $maskSize): void
    {
        $width  = $image->width();
        $height = $image->height();

        // Разрешаем только 3 или 5
        if (!in_array($maskSize, [3, 5], true)) {
            throw new \InvalidArgumentException('Mask size must be 3 or 5');
        }

        $offset = intdiv($maskSize, 2);

        // Маски
        if ($maskSize === 3) {

            if ($maskType === 'h1') {
                $mask = [
                    [1/9, 1/9, 1/9],
                    [1/9, 1/9, 1/9],
                    [1/9, 1/9, 1/9],
                ];
            } elseif ($maskType === 'h2') {
                $mask = [
                    [1/10, 1/10, 1/10],
                    [1/10, 2/10, 1/10],
                    [1/10, 1/10, 1/10],
                ];
            } else {
                // Gaussian 3×3
                $mask = [
                    [1/16, 2/16, 1/16],
                    [2/16, 4/16, 2/16],
                    [1/16, 2/16, 1/16],
                ];
            }

        } else { // 5×5 Gaussian

            // Gaussian 5×5 (нормализован)
            $mask = [
                [1,  4,  6,  4, 1],
                [4, 16, 24, 16, 4],
                [6, 24, 36, 24, 6],
                [4, 16, 24, 16, 4],
                [1,  4,  6,  4, 1],
            ];

            // Нормализация
            foreach ($mask as &$row) {
                foreach ($row as &$value) {
                    $value /= 256;
                }
            }
        }

        $source = clone $image;

        for ($y = $offset; $y < $height - $offset; $y++) {
            for ($x = $offset; $x < $width - $offset; $x++) {

                $sumR = 0.0;
                $sumG = 0.0;
                $sumB = 0.0;

                for ($my = -$offset; $my <= $offset; $my++) {
                    for ($mx = -$offset; $mx <= $offset; $mx++) {

                        $color  = $source->pickColor($x + $mx, $y + $my);
                        $weight = $mask[$my + $offset][$mx + $offset];

                        $sumR += $color->red()->value()   * $weight;
                        $sumG += $color->green()->value() * $weight;
                        $sumB += $color->blue()->value()  * $weight;
                    }
                }

                $r = max(0, min(255, (int)round($sumR)));
                $g = max(0, min(255, (int)round($sumG)));
                $b = max(0, min(255, (int)round($sumB)));

                $image->drawPixel($x, $y, new Color($r, $g, $b));
            }
        }
    }


    /**
     * Высокочастотная фильтрация
     */
    private function applyHighPassFilterOptimized($image, $maskType, $maskSize, $brightnessFactor): void
    {
        $width = $image->width();
        $height = $image->height();

        $maskSize = 3;
        $offset = (int)($maskSize / 2);

        if ($maskType === 'h4') {
            $mask = [[0, -1, 0], [-1, 5, -1], [0, -1, 0]];
        } elseif ($maskType === 'h5') {
            $mask = [[-1, -1, -1], [-1, 9, -1], [-1, -1, -1]];
        } else {
            $mask = [[-1, -2, -1], [-2, (int)(12 + $brightnessFactor), -2], [-1, -2, -1]];
        }

        for ($y = $offset; $y < $height - $offset; $y += 2) {
            for ($x = $offset; $x < $width - $offset; $x += 2) {
                $sumR = 0;
                $sumG = 0;
                $sumB = 0;

                for ($my = -$offset; $my <= $offset; $my++) {
                    for ($mx = -$offset; $mx <= $offset; $mx++) {
                        $color = $image->pickColor($x + $mx, $y + $my);
                        $weight = $mask[$my + $offset][$mx + $offset];

                        $sumR += (int)$color->red()->value() * $weight;
                        $sumG += (int)$color->green()->value() * $weight;
                        $sumB += (int)$color->blue()->value() * $weight;
                    }
                }

                $r = max(0, min(255, $sumR));
                $g = max(0, min(255, $sumG));
                $b = max(0, min(255, $sumB));

                $image->drawPixel($x, $y, new Color($r, $g, $b));

                if ($x + 1 < $width - $offset) {
                    $image->drawPixel($x + 1, $y, new Color($r, $g, $b));
                }
                if ($y + 1 < $height - $offset) {
                    $image->drawPixel($x, $y + 1, new Color($r, $g, $b));
                }
            }
        }
    }

    /**
     * Медианная фильтрация
     */
    private function applyMedianFilterOptimized($image, $maskSize): void
    {
        $width = $image->width();
        $height = $image->height();

        $maskSize = 3;
        $offset = (int)($maskSize / 2);

        for ($y = $offset; $y < $height - $offset; $y += 3) {
            for ($x = $offset; $x < $width - $offset; $x += 3) {
                $reds = [];
                $greens = [];
                $blues = [];

                for ($my = -$offset; $my <= $offset; $my++) {
                    for ($mx = -$offset; $mx <= $offset; $mx++) {
                        $color = $image->pickColor($x + $mx, $y + $my);
                        $reds[] = (int)$color->red()->value();
                        $greens[] = (int)$color->green()->value();
                        $blues[] = (int)$color->blue()->value();
                    }
                }

                sort($reds);
                sort($greens);
                sort($blues);

                $medianIndex = (int)(count($reds) / 2);
                $medianColor = new Color($reds[$medianIndex], $greens[$medianIndex], $blues[$medianIndex]);

                for ($my = -1; $my <= 1; $my++) {
                    for ($mx = -1; $mx <= 1; $mx++) {
                        if ($x + $mx >= 0 && $x + $mx < $width && $y + $my >= 0 && $y + $my < $height) {
                            $image->drawPixel($x + $mx, $y + $my, $medianColor);
                        }
                    }
                }
            }
        }
    }

    /**
     * Получение списка обработанных изображений (только текущие)
     */
    public function getProcessedImages(): JsonResponse
    {
        try {
            $images = [];

            // Текущее оригинальное изображение
            $originalFiles = glob(Storage::disk('public')->path('lab5/original/current_image.*'));
            if (!empty($originalFiles)) {
                $filename = basename($originalFiles[0]);
                $images[] = [
                    'type' => 'original',
                    'name' => $filename,
                    'url' => Storage::url('lab5/original/' . $filename),
                    'size' => Storage::disk('public')->size('lab5/original/' . $filename)
                ];
            }

            // Текущее изображение с шумом
            $noisedPath = Storage::disk('public')->path('lab5/noised/current_noised.jpg');
            if (file_exists($noisedPath)) {
                $images[] = [
                    'type' => 'noised',
                    'name' => 'current_noised.jpg',
                    'url' => Storage::url('lab5/noised/current_noised.jpg'),
                    'size' => Storage::disk('public')->size('lab5/noised/current_noised.jpg')
                ];
            }

            // Текущее отфильтрованное изображение
            $filteredPath = Storage::disk('public')->path('lab5/filtered/current_filtered.jpg');
            if (file_exists($filteredPath)) {
                $images[] = [
                    'type' => 'filtered',
                    'name' => 'current_filtered.jpg',
                    'url' => Storage::url('lab5/filtered/current_filtered.jpg'),
                    'size' => Storage::disk('public')->size('lab5/filtered/current_filtered.jpg')
                ];
            }

            return response()->json([
                'success' => true,
                'images' => $images
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении изображений: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистка всех обработанных изображений
     */
    public function clearAll(): JsonResponse
    {
        try {
            Storage::disk('public')->deleteDirectory('lab5');

            // Создаем пустые директории
            Storage::disk('public')->makeDirectory('lab5/original');
            Storage::disk('public')->makeDirectory('lab5/preview');
            Storage::disk('public')->makeDirectory('lab5/noised');
            Storage::disk('public')->makeDirectory('lab5/filtered');

            return response()->json([
                'success' => true,
                'message' => 'Все изображения удалены'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при очистке: ' . $e->getMessage()
            ], 500);
        }
    }
}
