<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

//Run-Length Encoding
class RleController extends Controller
{
    /**
     * Главная страница контроллера
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Кодирование текстовой последовательности с использованием RLE
     *
     * @param Request $request Входной запрос с полем 'input_text'
     * @return \Illuminate\Http\JsonResponse Возвращает JSON с закодированным текстом, декодированным текстом,
     *         количеством бит и коэффициентом сжатия
     */
    public function encodeText(Request $request)
    {
        $request->validate([
            'input_text' => 'required|string'
        ]);

        $input = $request->input_text;

        Log::info('$input', (array) $input);
        // Кодируем текст RLE-строку
        $encoded = $this->rleEncodeTextHuman($input);

        // Декодируем обратно для проверки корректности
        $decoded = $this->rleDecodeTextHuman($encoded);

        // - Оригинальная строка: каждый символ UTF-8 = 8 бит mb_strlen($input, '8bit') - БАЙТОВ в строке * 8 получилось в байтах Меджик
        $originalBits = mb_strlen($input, '8bit') * 8;
        Log::info('$originalBits', (array)$originalBits);
        // - Закодированная строка = длина строки * 8 бит
        $encodedBits = mb_strlen($encoded, '8bit') * 8;

        // Степень сжатия
        // ((оригинал - сжатый) / оригинал) * 100%
//        Сжатие (%) = [(Исходный_размер - Сжатый_размер) / Исходный_размер] × 100%
        $compressionRatio = $originalBits > 0 ?
            (($originalBits - $encodedBits) / $originalBits) * 100 : 0;

        return response()->json([
            'encoded' => $encoded,
            'decoded' => $decoded,
            'original_bits' => $originalBits,
            'encoded_bits' => $encodedBits,
            'compression_ratio' => round($compressionRatio, 2)
        ]);
    }

    /**
     * Кодирование загруженного изображения (монохромного)
     *
     * @param Request $request Входной запрос с полем 'image'
     * @return \Illuminate\Http\JsonResponse Возвращает JSON с закодированным изображением, размерами,
     *         количеством бит и коэффициентом сжатия
     */
    public function encodeImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:png,jpg,jpeg,bmp|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->getPathname();

            // Получаем размеры изображения
            $imageInfo = getimagesize($imagePath);
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Конвертируем в монохромное изображение ('0' = черный, '1' = белый)
            $monochromeData = $this->convertToMonochrome($imagePath);

            // Кодируем данные изображения с помощью RLE
            $encodedImage = $this->rleEncodeImageCounts($monochromeData);

            // Декодируем обратно для проверки
            $decodedImage = $this->rleDecodeImageFromCounts($encodedImage);

            // Биты: 1 бит на пиксель для монохромного изображения
            $originalBits = strlen($monochromeData);
            $encodedBits = strlen($encodedImage); // длина строкового представления
            $compressionRatio = $originalBits > 0 ?
                (($originalBits - $encodedBits) / $originalBits) * 100 : 0;

            // Проверка корректности декодирования
            $isValid = ($monochromeData === $decodedImage);

            Log::info('Image encoding completed', [
                'original_length' => strlen($monochromeData),
                'encoded_length' => strlen($encodedImage),
                'is_valid' => $isValid
            ]);

            return response()->json([
                'encoded_image' => $encodedImage,
                'width' => $width,
                'height' => $height,
                'original_bits' => $originalBits,
                'encoded_bits' => $encodedBits,
                'compression_ratio' => round($compressionRatio, 2),
                'is_valid' => $isValid,
                'monochrome_length' => strlen($monochromeData),
                'encoded_length' => strlen($encodedImage)
            ]);
        }

        return response()->json(['error' => 'Ошибка загрузки изображения'], 400);
    }

    /**
     * ==========================
     * Формат: "aaasssaa" -> "3a3s2a"
     * ==========================
     */
    private function rleEncodeTextHuman(string $input): string
    {
        if ($input === '') return '';

        // Разбиваем UTF-8 строку на массив символов
        $chars = preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        $resultParts = [];
        $count = 1;

        // Проходим по символам и считаем серии одинаковых
        for ($i = 1; $i <= $len; $i++) {
            if ($i < $len && $chars[$i] === $chars[$i - 1]) {
                $count++;
            } else {
                // Добавляем "количество + символ" в результат
                $resultParts[] = $count . $chars[$i - 1];
                $count = 1;
            }
        }

        return implode('', $resultParts);
    }

    /**
     * Декодирование человекочитаемого формата RLE
     * Формат: "3a3s2a" -> "aaasssaa"
     */
    private function rleDecodeTextHuman(string $encoded): string
    {
        if ($encoded === '') return '';

        $decoded = '';
        $i = 0;
        $len = mb_strlen($encoded, 'UTF-8');

        while ($i < $len) {
            // Читаем число (количество повторов)
            $numStr = '';
            while ($i < $len) {
                $ch = mb_substr($encoded, $i, 1, 'UTF-8');
                if (preg_match('/^[0-9]$/', $ch)) {
                    $numStr .= $ch;
                    $i++;
                } else {
                    break;
                }
            }

            if ($numStr === '') break; // некорректный формат

            // Читаем символ
            if ($i < $len) {
                $symbol = mb_substr($encoded, $i, 1, 'UTF-8');
                $i++;
                $decoded .= str_repeat($symbol, (int)$numStr);
            }
        }

        return $decoded;
    }

    /**
     * ===================================
     * IMAGE RLE в читаемом формате
     * Формат: "0:10 1:5 0:20"
     * ===================================
     */
    private function rleEncodeImageCounts(string $monochromeData): string
    {
        if ($monochromeData === '') return '';

        $parts = [];
        $count = 1;
        $len = strlen($monochromeData);

        for ($i = 1; $i <= $len; $i++) {
            if ($i < $len && $monochromeData[$i] === $monochromeData[$i - 1]) {
                $count++;
            } else {
                $value = $monochromeData[$i - 1];
                $parts[] = $value . ':' . $count;
                $count = 1;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Декодирование из читаемого RLE формата обратно в строку '0'/'1'
     */
    private function rleDecodeImageFromCounts(string $countsString): string
    {
        if (trim($countsString) === '') return '';

        $decoded = '';
        $groups = preg_split('/\s+/', trim($countsString));

        foreach ($groups as $group) {
            if (strpos($group, ':') === false) continue;
            list($value, $countStr) = explode(':', $group, 2);
            $decoded .= str_repeat($value === '1' ? '1' : '0', (int)$countStr);
        }

        return $decoded;
    }

    /**
     * Преобразование изображения в монохромное (черно-белое)
     * Используем формулу яркости (градации серого):
     * Gray = 0.299*R + 0.587*G + 0.114*B
     * > 128 -> белый ('1'), иначе черный ('0')
     */
    private function convertToMonochrome($imagePath)
    {
        $imageInfo = getimagesize($imagePath);
        $type = $imageInfo[2];

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                imagealphablending($image, false);
                imagesavealpha($image, false);
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp($imagePath);
                break;
            default:
                throw new \Exception('Неподдерживаемый формат изображения');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $monochromeData = '';

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $monochromeData .= $gray > 128 ? '1' : '0';
            }
        }

        imagedestroy($image);
        return $monochromeData;
    }
}
