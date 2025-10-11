<?php

namespace App\Http\Services;

class ColorConverterService
{
    /**
     * Поддерживаемые цветовые модели
     */
    const MODELS = ['RGB', 'CMYK', 'HSL', 'HSV', 'XYZ', 'LAB', 'YUV'];

    /**
     * Конвертация RGB в CMYK
     */
    public function rgbToCmyk($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $k = 1 - max($r, $g, $b);

        if ($k == 1) {
            return [0, 0, 0, 100];
        }

        $c = (1 - $r - $k) / (1 - $k);
        $m = (1 - $g - $k) / (1 - $k);
        $y = (1 - $b - $k) / (1 - $k);

        return [
            round($c * 100),
            round($m * 100),
            round($y * 100),
            round($k * 100)
        ];
    }

    /**
     * Конвертация CMYK в RGB
     */
    public function cmykToRgb($c, $m, $y, $k)
    {
        $c = $c / 100;
        $m = $m / 100;
        $y = $y / 100;
        $k = $k / 100;

        $r = 255 * (1 - $c) * (1 - $k);
        $g = 255 * (1 - $m) * (1 - $k);
        $b = 255 * (1 - $y) * (1 - $k);

        return [
            round($r),
            round($g),
            round($b)
        ];
    }

    /**
     * Конвертация RGB в XYZ
     */
    public function rgbToXyz($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        // Применяем гамма-коррекцию
        $r = ($r > 0.04045) ? pow(($r + 0.055) / 1.055, 2.4) : $r / 12.92;
        $g = ($g > 0.04045) ? pow(($g + 0.055) / 1.055, 2.4) : $g / 12.92;
        $b = ($b > 0.04045) ? pow(($b + 0.055) / 1.055, 2.4) : $b / 12.92;

        $r = $r * 100;
        $g = $g * 100;
        $b = $b * 100;

        $x = $r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375;
        $y = $r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750;
        $z = $r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041;

        return [
            round($x, 2),
            round($y, 2),
            round($z, 2)
        ];
    }

    /**
     * Конвертация XYZ в RGB
     */
    public function xyzToRgb($x, $y, $z)
    {
        $x = $x / 100;
        $y = $y / 100;
        $z = $z / 100;

        $r = $x * 3.2404542 + $y * -1.5371385 + $z * -0.4985314;
        $g = $x * -0.9692660 + $y * 1.8760108 + $z * 0.0415560;
        $b = $x * 0.0556434 + $y * -0.2040259 + $z * 1.0572252;

        // Обратная гамма-коррекция
        $r = ($r > 0.0031308) ? 1.055 * pow($r, 1/2.4) - 0.055 : 12.92 * $r;
        $g = ($g > 0.0031308) ? 1.055 * pow($g, 1/2.4) - 0.055 : 12.92 * $g;
        $b = ($b > 0.0031308) ? 1.055 * pow($b, 1/2.4) - 0.055 : 12.92 * $b;

        $r = max(0, min(255, round($r * 255)));
        $g = max(0, min(255, round($g * 255)));
        $b = max(0, min(255, round($b * 255)));

        return [$r, $g, $b];
    }

    /**
     * Конвертация RGB в HSL
     */
    public function rgbToHsl($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $delta / (1 - abs(2 * $l - 1));

            switch ($max) {
                case $r:
                    $h = 60 * fmod(($g - $b) / $delta, 6);
                    break;
                case $g:
                    $h = 60 * (($b - $r) / $delta + 2);
                    break;
                case $b:
                    $h = 60 * (($r - $g) / $delta + 4);
                    break;
            }

            if ($h < 0) {
                $h += 360;
            }
        }

        return [
            round($h),
            round($s * 100),
            round($l * 100)
        ];
    }

    /**
     * Конвертация HSL в RGB
     */
    public function hslToRgb($h, $s, $l)
    {
        $h = $h / 360;
        $s = $s / 100;
        $l = $l / 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($p, $q, $h + 1/3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1/3);
        }

        return [
            round($r * 255),
            round($g * 255),
            round($b * 255)
        ];
    }

    private function hueToRgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Конвертация RGB в HSV/HSB
     */
    public function rgbToHsv($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $v = $max;

        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $delta / $max;

            switch ($max) {
                case $r:
                    $h = 60 * fmod(($g - $b) / $delta, 6);
                    break;
                case $g:
                    $h = 60 * (($b - $r) / $delta + 2);
                    break;
                case $b:
                    $h = 60 * (($r - $g) / $delta + 4);
                    break;
            }

            if ($h < 0) {
                $h += 360;
            }
        }

        return [
            round($h),
            round($s * 100),
            round($v * 100)
        ];
    }

    /**
     * Конвертация HSV/HSB в RGB
     */
    public function hsvToRgb($h, $s, $v)
    {
        $h = $h / 360;
        $s = $s / 100;
        $v = $v / 100;

        if ($s == 0) {
            $r = $g = $b = $v;
        } else {
            $i = floor($h * 6);
            $f = $h * 6 - $i;
            $p = $v * (1 - $s);
            $q = $v * (1 - $f * $s);
            $t = $v * (1 - (1 - $f) * $s);

            switch ($i % 6) {
                case 0: $r = $v; $g = $t; $b = $p; break;
                case 1: $r = $q; $g = $v; $b = $p; break;
                case 2: $r = $p; $g = $v; $b = $t; break;
                case 3: $r = $p; $g = $q; $b = $v; break;
                case 4: $r = $t; $g = $p; $b = $v; break;
                case 5: $r = $v; $g = $p; $b = $q; break;
            }
        }

        return [
            round($r * 255),
            round($g * 255),
            round($b * 255)
        ];
    }

    /**
     * Конвертация RGB в Lab через XYZ
     */
    public function rgbToLab($r, $g, $b)
    {
        $xyz = $this->rgbToXyz($r, $g, $b);
        return $this->xyzToLab($xyz[0], $xyz[1], $xyz[2]);
    }

    /**
     * Конвертация Lab в RGB через XYZ
     */
    public function labToRgb($l, $a, $b)
    {
        $xyz = $this->labToXyz($l, $a, $b);
        return $this->xyzToRgb($xyz[0], $xyz[1], $xyz[2]);
    }

    private function xyzToLab($x, $y, $z)
    {
        // D65 reference white
        $refX = 95.047;
        $refY = 100.000;
        $refZ = 108.883;

        $x = $x / $refX;
        $y = $y / $refY;
        $z = $z / $refZ;

        $x = ($x > 0.008856) ? pow($x, 1/3) : (7.787 * $x) + (16/116);
        $y = ($y > 0.008856) ? pow($y, 1/3) : (7.787 * $y) + (16/116);
        $z = ($z > 0.008856) ? pow($z, 1/3) : (7.787 * $z) + (16/116);

        $l = (116 * $y) - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return [
            round($l, 2),
            round($a, 2),
            round($b, 2)
        ];
    }

    private function labToXyz($l, $a, $b)
    {
        // D65 reference white
        $refX = 95.047;
        $refY = 100.000;
        $refZ = 108.883;

        $y = ($l + 16) / 116;
        $x = $a / 500 + $y;
        $z = $y - $b / 200;

        $x = ($x > 0.206893) ? pow($x, 3) : ($x - 16/116) / 7.787;
        $y = ($y > 0.206893) ? pow($y, 3) : ($y - 16/116) / 7.787;
        $z = ($z > 0.206893) ? pow($z, 3) : ($z - 16/116) / 7.787;

        return [
            round($x * $refX, 2),
            round($y * $refY, 2),
            round($z * $refZ, 2)
        ];
    }

    /**
     * Конвертация RGB в YUV
     */
    public function rgbToYuv($r, $g, $b)
    {
        $y = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        $u = -0.14713 * $r - 0.28886 * $g + 0.436 * $b;
        $v = 0.615 * $r - 0.51499 * $g - 0.10001 * $b;

        return [
            round($y),
            round($u),
            round($v)
        ];
    }

    /**
     * Конвертация YUV в RGB
     */
    public function yuvToRgb($y, $u, $v)
    {
        $r = $y + 1.13983 * $v;
        $g = $y - 0.39465 * $u - 0.58060 * $v;
        $b = $y + 2.03211 * $u;

        $r = max(0, min(255, round($r)));
        $g = max(0, min(255, round($g)));
        $b = max(0, min(255, round($b)));

        return [$r, $g, $b];
    }

    /**
     * Универсальный конвертер между любыми моделями
     */
    public function convert($fromModel, $toModel, $values)
    {
        // Сначала конвертируем в RGB
        $rgb = $this->toRgb($fromModel, $values);

        // Затем из RGB в целевую модель
        return $this->fromRgb($toModel, $rgb);
    }

    /**
     * Получить все цветовые представления из любого формата
     */
    public function getAllColorValues($fromModel, $values)
    {
        $rgb = $this->toRgb($fromModel, $values);

        $result = [];
        foreach (self::MODELS as $model) {
            $result[$model] = $this->fromRgb($model, $rgb);
        }

        return $result;
    }

    /**
     * Получить HEX представление цвета из RGB
     */
    public function rgbToHex($r, $g, $b)
    {
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Получить координаты для цветового круга из HSL
     */
    public function hslToCircleCoordinates($h, $s, $l)
    {
        $radius = ($s / 100) * 50; // 50% от максимального радиуса
        $angle = deg2rad($h);

        $x = 50 + $radius * cos($angle);
        $y = 50 - $radius * sin($angle);

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'lightness' => $l
        ];
    }

    private function toRgb($model, $values)
    {
        switch (strtoupper($model)) {
            case 'RGB':
                return $values;
            case 'CMYK':
                return $this->cmykToRgb(...$values);
            case 'HSL':
                return $this->hslToRgb(...$values);
            case 'HSV':
            case 'HSB':
                return $this->hsvToRgb(...$values);
            case 'XYZ':
                return $this->xyzToRgb(...$values);
            case 'LAB':
                return $this->labToRgb(...$values);
            case 'YUV':
                return $this->yuvToRgb(...$values);
            default:
                throw new \Exception("Unsupported color model: $model");
        }
    }

    private function fromRgb($model, $rgb)
    {
        switch (strtoupper($model)) {
            case 'RGB':
                return $rgb;
            case 'CMYK':
                return $this->rgbToCmyk(...$rgb);
            case 'HSL':
                return $this->rgbToHsl(...$rgb);
            case 'HSV':
            case 'HSB':
                return $this->rgbToHsv(...$rgb);
            case 'XYZ':
                return $this->rgbToXyz(...$rgb);
            case 'LAB':
                return $this->rgbToLab(...$rgb);
            case 'YUV':
                return $this->rgbToYuv(...$rgb);
            default:
                throw new \Exception("Unsupported color model: $model");
        }
    }
}
