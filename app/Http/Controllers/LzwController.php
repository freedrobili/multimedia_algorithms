<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 *
 * Биты до сжатия:
 * Исходная строка: abacabadabacabae = 16 символов
 *
 * Каждый символ в ASCII занимает 8 бит (1 байт)
 *
 * Итого: 16 × 8 = 128 бит
 *
 * 9 бит позволяют хранить числа от 0 до 511 (2⁹-1 = 511)
 *
 * 260 ≤ 511, значит достаточно 9 бит
 *
 * Количество кодов в последовательности = 11 (97, 98, 97, 99, 256, 97, 100, 260, 259, 257, 101)
 *
 * Каждый код занимает 9 бит
 *
 * Итого: 11 × 9 = 99 бит
 *
 * Проверка:
 * Биты до: 128 бит (16 символов × 8 бит)
 *
 * Биты после: 99 бит (11 кодов × 9 бит)
 *
 * Степень сжатия: (128 - 99) / 128 × 100% ≈ 22.66%
 *
 * Почему именно 9 бит? Важный нюанс!
 * В реальной реализации LZW размер кода динамически увеличивается по мере роста словаря:
 *
 * Сначала используют 9 бит (коды 0-511)
 *
 * Когда словарь заполняет все 512 записей (0-511), переходят на 10 бит (0-1023)
 *
 * Потом на 11, 12 бит и т.д.
 *
 * В данном примере:
 *
 * Максимальный код 260
 *
 * 260 < 512, значит достаточно 9 бит на весь процесс
 *
 * Поэтому считаем фиксированно по 9 бит на код
 * LzwController
 *
 *  - изображений (градации серого) — извлечение яркостей пикселей в массив 0..255
 *
 * @package App\Http\Controllers
 */
class LzwController extends Controller
{
    private array $lastSymbolToCode = [];
    private array $lastCodeToSymbol = [];
    private array $encodingSteps = [];
    private array $decodingSteps = [];

    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('lab_4');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function encodeText(Request $request): JsonResponse
    {
        $input = (string)$request->input('text', '');
        $showSteps = (bool)$request->input('show_steps', false);

        if ($input === '') {
            return response()->json(['error' => 'Введите текст для кодирования']);
        }

        $this->encodingSteps = [];

        $encoded = $this->lzwEncode($input, $showSteps);

        $originalBits = strlen($input) * 8;
        $encodedBits = $this->calculateEncodedBits($encoded);
        // Процент сжатия: (исход - закодировано) / исход * 100
        $compressionRatio = $originalBits > 0 ? (($originalBits - $encodedBits) / $originalBits) * 100 : 0.0;

        $dictionaryForOutput = $this->formatDictionaryForOutput();

        return response()->json([
            'original' => $input,
            'encoded' => $encoded,
            'encoded_string' => implode(' ', $encoded),
            'original_bits' => $originalBits,
            'encoded_bits' => $encodedBits,
            'compression_ratio' => round($compressionRatio, 2),
            'steps' => $showSteps ? $this->encodingSteps : [],
            'dictionary' => $showSteps ? $dictionaryForOutput : []
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function decodeText(Request $request): JsonResponse
    {
        $encodedString = (string)$request->input('encoded_text', '');
        $showSteps = (bool)$request->input('show_steps', false);

        if (trim($encodedString) === '') {
            return response()->json(['error' => 'Введите закодированную последовательность']);
        }

        $encoded = array_map('intval', preg_split('/\s+/', trim($encodedString)));

        $this->decodingSteps = [];
        $decodedBytes = $this->lzwDecode($encoded, $showSteps); // возвращает байтовую строку

        // Для удобства интерфейса возвращаем decoded как байтовую строку (возможно UTF-8)
        $decoded = $decodedBytes;

        return response()->json([
            'encoded' => $encoded,
            'decoded' => $decoded,
            'steps' => $showSteps ? $this->decodingSteps : [],
            'dictionary' => $showSteps ? $this->formatDictionaryForOutput() : []
        ]);
    }


    /**
     * encodeImage
     *
     * Кодирует загруженное изображение (multipart file input 'image') алгоритмом LZW:
     *  - Преобразует изображение в градации серого (greyscale)
     *  - Собирает массив яркостей пикселей 0..255 (по строкам)
     *  - Вызывает lzwEncode для этого массива (внутри lzwEncode элементы-массивы приводятся к байтовым символам)
     *  - Считает статистику и сохраняет JSON с результатом кодирования в storage
     *
     * Примечания и важные детали реализации:
     *  - Для получения значений R,G,B используется Intervention Image и метод pickColor.
     *    В оригинальном коде pickColor может возвращать объект Color, поэтому здесь извлекаются
     *    значения через ->red()->value() и т.д. (зависит от версии библиотеки).
     *  - В результате записывается JSON файл: имя файла = <original>_lzw_encoded.json в storage (root)
     *    (в production вы, возможно, захотите хранить в папке, следить за коллизией имён и т.д.)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function encodeImage(Request $request): JsonResponse
    {
        try {
            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'Выберите изображение для обработки']);
            }

            $image = $request->file('image');
            $imagePath = $image->getPathname();

            // Загружаем изображение и приводим к оттенкам серого
            $img = $this->imageManager->read($imagePath);
            $img->greyscale();

            $width = $img->width();
            $height = $img->height();

            $pixels = [];
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $color = $img->pickColor($x, $y);
                    $r = $color->red()->value();
                    $g = $color->green()->value();
                    $b = $color->blue()->value();

                    // Преобразование RGB в яркость (luma) по стандартной формуле:
                    // Y = 0.299 R + 0.587 G + 0.114 B
                    $brightness = (int) round($r * 0.299 + $g * 0.587 + $b * 0.114);

                    // Ограничиваем 0..255
                    if ($brightness < 0) $brightness = 0;
                    if ($brightness > 255) $brightness = 255;

                    $pixels[] = $brightness;
                }
            }

            $this->encodingSteps = [];
            $encoded = $this->lzwEncode($pixels, false);

            $originalBits = count($pixels) * 8;
            $encodedBits = $this->calculateEncodedBits($encoded);
            $compressionRatio = $originalBits > 0 ? (($originalBits - $encodedBits) / $originalBits) * 100 : 0.0;

            $filenameBase = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $encodedData = [
                'width' => $width,
                'height' => $height,
                'encoded' => $encoded,
                'original_bits' => $originalBits,
                'encoded_bits' => $encodedBits,
                'compression_ratio' => round($compressionRatio, 2)
            ];

            $encodedFilename = $filenameBase . '_lzw_encoded.json';
            Storage::put($encodedFilename, json_encode($encodedData));

            return response()->json([
                'success' => true,
                'original_size_bits' => $originalBits,
                'encoded_size_bits' => $encodedBits,
                'compression_ratio' => round($compressionRatio, 2),
                'pixels_count' => count($pixels),
                'codes_count' => count($encoded),
                'encoded_filename' => $encodedFilename,
                'image_info' => [
                    'width' => $width,
                    'height' => $height,
                    'format' => $img->origin()->mimeType()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка обработки изображения: ' . $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function decodeImage(Request $request): JsonResponse
    {
        Log::info('=== DECODE IMAGE PROCESS START ===');
        Log::info('Filename: "' . $request->input('filename', '') . '"');

        $filename = (string)$request->input('filename', '');

        if ($filename === '' || !Storage::exists($filename)) {
            Log::error('File not found: ' . $filename);
            return response()->json(['error' => 'Укажите корректное имя JSON-файла в storage']);
        }

        try {
            $json = Storage::get($filename);
            Log::info('File read: ' . strlen($json) . ' bytes');

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg());
                return response()->json(['error' => 'Ошибка парсинга JSON: ' . json_last_error_msg()]);
            }

            Log::info('JSON keys: ' . implode(', ', array_keys($data)));

            if (!isset($data['width'], $data['height'], $data['encoded'])) {
                Log::error('Missing required fields');
                return response()->json(['error' => 'Неверный формат JSON файла']);
            }

            $width = (int)$data['width'];
            $height = (int)$data['height'];
            $encoded = array_map('intval', $data['encoded']);

            Log::info("Dimensions: {$width}x{$height}, encoded data: " . count($encoded) . " items");
            if (!empty($encoded)) {
                Log::info('Encoded range: ' . min($encoded) . '-' . max($encoded));
            }

            if (empty($encoded)) {
                return response()->json(['error' => 'Закодированные данные пусты']);
            }

            Log::info('Starting LZW decode...');
            try {
                $decodedBytes = $this->lzwDecode($encoded, false, true);
                Log::info('LZW decode completed: ' . strlen($decodedBytes) . ' bytes');

                if (empty($decodedBytes)) {
                    return response()->json(['error' => 'Декодированные данные пусты']);
                }

                // Преобразуем байтовую строку в массив чисел 0..255
                $bytes = [];
                $len = strlen($decodedBytes);
                for ($i = 0; $i < $len; $i++) {
                    $bytes[] = ord($decodedBytes[$i]);
                }

                Log::info('Byte array: ' . count($bytes) . ' bytes, range: ' . min($bytes) . '-' . max($bytes));

            } catch (\Exception $e) {
                Log::error('LZW decoding failed: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Ошибка декодирования LZW: ' . $e->getMessage()
                ]);
            }

            $expectedSize = $width * $height;
            $actualSize = count($bytes);
            Log::info("Size check: expected {$expectedSize}, actual {$actualSize}");

            // Если расхождение — подрезаем или дополняем нулями
            if ($actualSize !== $expectedSize) {
                Log::warning("Size mismatch, adjusting...");
                if ($actualSize > $expectedSize) {
                    $bytes = array_slice($bytes, 0, $expectedSize);
                } else {
                    $bytes = array_pad($bytes, $expectedSize, 0);
                }
            }

            Log::info("Creating image using GD...");

            try {
                $image = imagecreate($width, $height);

                // Заполняем палитру градаций серого (0..255)
                for ($i = 0; $i < 256; $i++) {
                    imagecolorallocate($image, $i, $i, $i);
                }

                Log::info('Setting pixels...');
                $idx = 0;
                $lastProgress = 0;

                // Устанавливаем пиксели построчно
                for ($y = 0; $y < $height; $y++) {
                    $progress = (int)($y * 100 / $height);
                    if ($progress >= $lastProgress + 10) {
                        Log::info("Progress: {$progress}% ({$y}/{$height} rows)");
                        $lastProgress = $progress;
                    }

                    for ($x = 0; $x < $width; $x++) {
                        $val = $bytes[$idx] ?? 0;
                        if ($val < 0) $val = 0;
                        if ($val > 255) $val = 255;

                        // В imagesetpixel третий параметр — индекс цвета из палитры, т.е. значение 0..255
                        imagesetpixel($image, $x, $y, $val);
                        $idx++;
                    }
                }

                Log::info('Pixel setting completed');

                $outFilename = pathinfo($filename, PATHINFO_FILENAME) . '_decoded.png';
                $tempPath = storage_path('app/public/' . $outFilename);

                Log::info("Saving image to public storage: " . $tempPath);

                imagepng($image, $tempPath);
                imagedestroy($image);

                Log::info('Image saved successfully to: ' . $tempPath);

                if (!file_exists($tempPath)) {
                    throw new \Exception('Failed to create image file');
                }

                $fileSize = filesize($tempPath);
                Log::info('File created, size: ' . $fileSize . ' bytes');

            } catch (\Exception $e) {
                Log::error('Image creation failed: ' . $e->getMessage());
                if (isset($image)) {
                    imagedestroy($image);
                }
                return response()->json(['error' => 'Ошибка создания изображения: ' . $e->getMessage()]);
            }

            Log::info('=== PROCESS COMPLETED SUCCESSFULLY ===');

            return response()->json([
                'success' => true,
                'restored_image' => $outFilename,
                'width' => $width,
                'height' => $height,
                'pixels_restored' => count($bytes),
                'expected_pixels' => $expectedSize,
                'message' => 'Изображение успешно восстановлено'
            ]);

        } catch (\Exception $e) {
            Log::error('Processing failed: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Ошибка при декодировании изображения: ' . $e->getMessage(),
                'file' => $filename
            ]);
        }
    }

    /**
     * lzwEncode
     *
     * Кодирование алгоритмом LZW.
     *
     * Общая логика:
     *  - инициализируем словарь 0..255
     *  - проходим через последовательность, объединяя текущую последовательность и следующий символ,
     *    если объединённая последовательность уже есть в словаре — продолжаем, иначе:
     *       - выводим код текущей последовательности
     *       - добавляем комбинированную последовательность в словарь под новым кодом
     *       - current = следующий символ
     *  - в конце выводим код для текущего
     *
     * @param string|array $input
     * @param bool $showSteps
     * @return array<int>
     */
    private function lzwEncode($input, bool $showSteps = false): array
    {
        $symbols = [];
        if (is_array($input)) {
            foreach ($input as $v) {
                $val = (int)$v & 0xFF;
                $symbols[] = chr($val);
            }
        } else {
            $symbols = str_split($input);
        }

        $symbolToCode = [];
        $codeToSymbol = [];
        for ($i = 0; $i < 256; $i++) {
            $ch = chr($i);
            $symbolToCode[$ch] = $i;
            $codeToSymbol[$i] = $ch;
        }
        $dictSize = 256;

        $encoded = [];

        if (count($symbols) === 0) {
            $this->lastSymbolToCode = $symbolToCode;
            $this->lastCodeToSymbol = $codeToSymbol;
            return $encoded;
        }

        $current = $symbols[0];

        if ($showSteps) {
            $this->encodingSteps[] = ['action' => 'init', 'dict_size' => $dictSize, 'current' => $current];
        }

        for ($i = 1; $i < count($symbols); $i++) {
            $c = $symbols[$i];
            $combined = $current . $c;

            if (isset($symbolToCode[$combined])) {
                $current = $combined;
                if ($showSteps) {
                    $this->encodingSteps[] = ['action' => 'extend', 'current' => $this->visibleString($current)];
                }
            } else {
                // Выводим код текущей последовательности
                $encoded[] = $symbolToCode[$current];

                // Добавляем combined в словарь с новым кодом
                $symbolToCode[$combined] = $dictSize;
                $codeToSymbol[$dictSize] = $combined;
                $dictSize++;

                if ($showSteps) {
                    $this->encodingSteps[] = [
                        'action' => 'output_and_add',
                        'output_code' => end($encoded),
                        'added_symbol' => $this->visibleString($combined),
                        'added_code' => $dictSize - 1
                    ];
                }

                // current = следующий символ (c)
                $current = $c;
            }
        }

        // Вывод кода для последнего current
        $encoded[] = $symbolToCode[$current];
        if ($showSteps) {
            $this->encodingSteps[] = ['action' => 'final_output', 'output_code' => end($encoded)];
        }

        // Сохраняем последние словари для вывода/диагностики
        $this->lastSymbolToCode = $symbolToCode;
        $this->lastCodeToSymbol = $codeToSymbol;

        return $encoded;
    }

    /**
     * lzwDecode
     *
     * Декодирование массива кодов LZW в байтовую строку.
     *
     * Параметры:
     *  - $encoded (array<int>) — входная последовательность кодов
     *  - $showSteps (bool) — если true — записывать шаги в $this->decodingSteps
     *  - $robust (bool) — если true, попытаться продолжить декодирование при некоторых ошибках,
     *       вставляя fallback-байты вместо выброса исключения (полезно для повреждённых данных)
     *
     * Поведение:
     *  - инициализируем словарь 0..255
     *  - читаем первый код, добавляем соответствующий символ в результат
     *  - для каждого следующего кода:
     *      - если код присутствует в словаре — берем соответствующую строку
     *      - иначе если код == dictSize — используем prevString + prevString[0] (специальный случай LZW)
     *      - иначе — ошибка (или fallback при $robust)
     *      - добавляем в результат найденную строку
     *      - добавляем новую запись в словарь: prevString + currentString[0]
     *
     * @param array $encoded
     * @param bool $showSteps
     * @param bool $robust
     * @return string
     * @throws \Exception
     */
    private function lzwDecode(array $encoded, bool $showSteps = false, bool $robust = false): string
    {
        if (count($encoded) === 0) {
            $this->lastSymbolToCode = [];
            $this->lastCodeToSymbol = [];
            return '';
        }

        $errors = [];

        try {
            // Инициализация словаря code => symbol
            $codeToSymbol = [];
            for ($i = 0; $i < 256; $i++) {
                $codeToSymbol[$i] = chr($i);
            }
            $dictSize = 256;

            // Первый код
            $firstCode = (int)$encoded[0];

            // Валидация первого кода: ожидаем 0..dictSize-1
            if ($firstCode < 0 || $firstCode >= $dictSize) {
                if ($robust) {
                    // В режиме robustness используем младший байт
                    $firstCode = $firstCode & 0xFF;
                    if ($firstCode >= 256) $firstCode = 0;
                } else {
                    throw new \Exception("Первый код вне диапазона: $firstCode");
                }
            }

            // Начальный результат — символ для первого кода (или fallback)
            $result = $codeToSymbol[$firstCode] ?? chr($firstCode & 0xFF);
            $prevCode = $firstCode;

            if ($showSteps) {
                $this->decodingSteps[] = [
                    'action' => 'first',
                    'code' => $firstCode,
                    'symbol' => $this->visibleString($result)
                ];
            }

            // Основной цикл: для каждого следующего кода строим текущую строку
            for ($i = 1; $i < count($encoded); $i++) {
                $currCode = (int)$encoded[$i];

                try {
                    $currentString = '';

                    if (isset($codeToSymbol[$currCode])) {
                        // Код есть в словаре — берём напрямую
                        $currentString = $codeToSymbol[$currCode];
                    } else if ($currCode == $dictSize) {
                        // Специфический LZW случай
                        $prevString = $codeToSymbol[$prevCode];
                        $currentString = $prevString . $prevString[0];
                    } else {
                        // Неизвестный код
                        if ($robust) {
                            $currentString = chr($currCode & 0xFF);
                            $errors[] = "Неизвестный код $currCode на позиции $i";
                        } else {
                            throw new \Exception("Неизвестный код: $currCode на позиции $i");
                        }
                    }

                    // Добавляем найденную строку в результирующую последовательность
                    $result .= $currentString;

                    // ============ ИСПРАВЛЕНИЕ ЗДЕСЬ ============
                    if ($showSteps) {
                        // ВАЖНО: сначала добавляем шаг с ДЕКОДИРОВАННОЙ строкой
                        $this->decodingSteps[] = [
                            'action' => 'decode',
                            'step' => $i + 1, // номер шага (начиная с 2)
                            'code' => $currCode,
                            'decoded_symbol' => $this->visibleString($currentString),
                            'current_output' => $this->visibleString($result) // весь накопленный вывод
                        ];
                    }
                    // ===========================================

                    // Добавляем новую запись в словарь: prevString + currentString[0]
                    if ($dictSize < 65536) { //16-битные — словарь до 65536 записей
                        $prevString = $codeToSymbol[$prevCode];
                        $newEntry = $prevString . $currentString[0];
                        $codeToSymbol[$dictSize] = $newEntry;

                        if ($showSteps) {
                            // А ЭТО уже второй шаг - добавление в словарь
                            $this->decodingSteps[] = [
                                'action' => 'add_to_dict',
                                'new_code' => $dictSize,
                                'new_symbol' => $this->visibleString($newEntry)
                            ];
                        }

                        $dictSize++;
                    }

                    // Следующий шаг — предыдущий код становится текущим
                    $prevCode = $currCode;

                } catch (\Exception $inner) {
                    if ($robust) {
                        // В режиме robustness — логируем ошибку и вставляем нулевой байт
                        $errors[] = [
                            'index' => $i,
                            'code' => $currCode,
                            'message' => $inner->getMessage()
                        ];
                        $result .= chr(0);
                        continue;
                    } else {
                        throw $inner;
                    }
                }
            }

            // Сохраняем словари для отладки и отображения
            $this->lastCodeToSymbol = $codeToSymbol;
            $this->lastSymbolToCode = array_flip($codeToSymbol);

            // Если были ошибки и showSteps=true — добавляем их в шаги
            if (!empty($errors) && $showSteps) {
                foreach ($errors as $error) {
                    $this->decodingSteps[] = [
                        'action' => 'error',
                        'message' => is_array($error) ? ($error['message'] ?? json_encode($error)) : $error
                    ];
                }
            }

            return $result;

        } catch (\Exception $e) {
            // Логируем для отладки
            Log::error('LZW decode error: ' . $e->getMessage());
            Log::error('Encoded data sample: ' . json_encode(array_slice($encoded, 0, 10)));

            if ($robust) {
                // В режиме robustness — попытаемся вернуть простой fallback: преобразуем каждый код в младший байт
                $fallback = '';
                foreach ($encoded as $code) {
                    $fallback .= chr($code & 0xFF);
                }
                return $fallback;
            }

            // Иначе пробрасываем исключение наружу
            throw new \Exception("Unable to decode input: " . $e->getMessage());
        }
    }

    /* ------------------------ Утилитарные методы ------------------------ */

    /**
     * calculateEncodedBits
     *
     * Упрощённый подсчёт количества бит, требующихся для хранения массива кодов.
     * Подход:
     *  - Находим максимальный код в массиве
     *  - Вычисляем минимальную ширину в битах: ceil(log2(maxCode+1)), минимум 1 бит
     *  - Умножаем на количество кодов
     *
     * Это упрощённая модель; реальные реализации LZW хранят кодовое поле с переменной
     * шириной (увеличивающейся по мере роста словаря) и могут использовать битовый буфер.
     *
     * @param array<int> $encoded
     * @return int Количество бит
     */
    private function calculateEncodedBits(array $encoded): int
    {
        if (count($encoded) === 0) {
            return 0;
        }
        $maxCode = max($encoded);
        // bitsPerCode = ceil(log2(maxCode + 1)), минимум 1
        $bitsPerCode = (int) max(1, ceil(log($maxCode + 1, 2)));
        return count($encoded) * $bitsPerCode;
    }

    /**
     * formatDictionaryForOutput
     *
     * Формирует читаемую версию словаря для вывода на фронтенд или для отладки.
     * Предпочтение отдаём lastCodeToSymbol (code => symbol). Если оно пустое — используем обратный
     * mapping lastSymbolToCode.
     *
     * Возвращает массив: code => readableString
     *
     * @return array<int,string>
     */
    private function formatDictionaryForOutput(): array
    {
        $out = [];
        if (!empty($this->lastCodeToSymbol)) {
            foreach ($this->lastCodeToSymbol as $code => $symbol) {
                $out[$code] = $this->visibleString($symbol);
            }
        } else {
            foreach ($this->lastSymbolToCode as $symbol => $code) {
                $out[$code] = $this->visibleString($symbol);
            }
        }
        return $out;
    }

    /**
     * visibleString
     *
     * Преобразует последовательность байтов (строку) в читабельное представление,
     * подходящее для отладки. Пример вывода: "65:'A',66:'B',10:'.'"
     *
     * Логика:
     *  - Для каждого байта вычисляем ord
     *  - Если байт — печатаемый ASCII (32..126) — показываем символ, иначе точку '.'
     *  - Возвращаем композицию "{ord}:'{char}'" через запятую
     *
     * @param string $s Строка байтов
     * @return string Читаемая строка
     */
    private function visibleString(string $s): string
    {
        $parts = [];
        $bytes = str_split($s);
        foreach ($bytes as $b) {
            $ord = ord($b);
            $char = ($ord >= 32 && $ord <= 126) ? $b : '.';
            $parts[] = "{$ord}:'{$char}'";
        }
        return implode(',', $parts);
    }
}
