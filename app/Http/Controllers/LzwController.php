<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager; // ← ДОБАВЬТЕ ЭТОТ ИМПОРТ

class LzwController extends Controller
{
    // Для отображения шагов/словаря наружу мы будем сохранять последние словари
    private array $lastSymbolToCode = [];
    private array $lastCodeToSymbol = [];
    private array $encodingSteps = [];
    private array $decodingSteps = [];
    private ImageManager $imageManager; // ← ДОБАВЬТЕ ЭТО СВОЙСТВО

    public function __construct()
    {
        // Инициализируем ImageManager
        $this->imageManager = new ImageManager(new Driver());
    }

    public function index()
    {
        return view('lab_4');
    }

    /**
     * Кодирование текста алгоритмом LZW
     */
    public function encodeText(Request $request): JsonResponse
    {
        $input = (string)$request->input('text', '');
        $showSteps = (bool)$request->input('show_steps', false);

        if ($input === '') {
            return response()->json(['error' => 'Введите текст для кодирования']);
        }

        // Сброс шагов
        $this->encodingSteps = [];

        // Кодируем (в реализации мы работаем на уровне байтов)
        $encoded = $this->lzwEncode($input, $showSteps);

        // Статистика
        $originalBits = strlen($input) * 8; // strlen в байтах
        $encodedBits = $this->calculateEncodedBits($encoded);
        $compressionRatio = $originalBits > 0 ? (($originalBits - $encodedBits) / $originalBits) * 100 : 0.0;

        // Подготовка человекочитаемого словаря для вывода: code => symbol (как ord и видимый)
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
     * Декодирование текста алгоритмом LZW (вход: строка кодов, разделённых пробелом)
     */
    public function decodeText(Request $request): JsonResponse
    {
        $encodedString = (string)$request->input('encoded_text', '');
        $showSteps = (bool)$request->input('show_steps', false);

        if (trim($encodedString) === '') {
            return response()->json(['error' => 'Введите закодированную последовательность']);
        }

        // Преобразуем строку в массив целых кодов
        $encoded = array_map('intval', preg_split('/\s+/', trim($encodedString)));

        $this->decodingSteps = [];
        $decodedBytes = $this->lzwDecode($encoded, $showSteps); // возвращает байтовую строку

        // Для текста мы считаем, что исходный ввод был байтовой строкой.
        // Если исходно вводили UTF-8 символы — декодированная байтовая последовательность может требовать интерпретации.
        $decoded = $decodedBytes;

        return response()->json([
            'encoded' => $encoded,
            'decoded' => $decoded,
            'steps' => $showSteps ? $this->decodingSteps : [],
            'dictionary' => $showSteps ? $this->formatDictionaryForOutput() : []
        ]);
    }

    /**
     * Кодирование изображения алгоритмом LZW
     * Возвращает JSON-файл со структуриой: width,height, encoded (массив кодов), original_bits, encoded_bits, compression_ratio
     */
    /**
     * Кодирование изображения алгоритмом LZW
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

            // Собираем байтовый массив яркостей (0..255)
            $pixels = [];
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    // ИСПРАВЛЕННАЯ СТРОКА - получаем числовые значения из объектов Color
                    $color = $img->pickColor($x, $y);
                    $r = $color->red()->value(); // получаем числовое значение красного канала
                    $g = $color->green()->value(); // получаем числовое значение зеленого канала
                    $b = $color->blue()->value(); // получаем числовое значение синего канала

                    $brightness = (int) round($r * 0.299 + $g * 0.587 + $b * 0.114);
                    // Clamp 0..255
                    if ($brightness < 0) $brightness = 0;
                    if ($brightness > 255) $brightness = 255;
                    $pixels[] = $brightness;
                }
            }

            // Остальной код без изменений...
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
     * Декодирование изображения из ранее сохранённого JSON файла (в storage)
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
            Log::info('Encoded range: ' . min($encoded) . '-' . max($encoded));

            if (empty($encoded)) {
                return response()->json(['error' => 'Закодированные данные пусты']);
            }

            // LZW декодирование
            Log::info('Starting LZW decode...');
            try {
                $decodedBytes = $this->lzwDecode($encoded, false, true);
                Log::info('LZW decode completed: ' . strlen($decodedBytes) . ' bytes');

                if (empty($decodedBytes)) {
                    return response()->json(['error' => 'Декодированные данные пусты']);
                }

                // Преобразуем строку в массив байтов
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

            // Проверяем размер
            $expectedSize = $width * $height;
            $actualSize = count($bytes);
            Log::info("Size check: expected {$expectedSize}, actual {$actualSize}");

            if ($actualSize !== $expectedSize) {
                Log::warning("Size mismatch, adjusting...");
                if ($actualSize > $expectedSize) {
                    $bytes = array_slice($bytes, 0, $expectedSize);
                } else {
                    $bytes = array_pad($bytes, $expectedSize, 0);
                }
            }

            // СОЗДАЕМ ИЗОБРАЖЕНИЕ ПРАВИЛЬНЫМ СПОСОБОМ
            Log::info("Creating image using GD...");

            try {
                // Создаем изображение вручную используя GD
                $image = imagecreate($width, $height);

                // Создаем палитру градаций серого
                for ($i = 0; $i < 256; $i++) {
                    imagecolorallocate($image, $i, $i, $i);
                }

                Log::info('Setting pixels...');
                $idx = 0;
                $lastProgress = 0;

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

                        imagesetpixel($image, $x, $y, $val);
                        $idx++;
                    }
                }

                Log::info('Pixel setting completed');

                // Сохранение изображения - ИСПРАВЛЕН ПУТЬ
                $outFilename = pathinfo($filename, PATHINFO_FILENAME) . '_decoded.png';

                // Сохраняем прямо в public storage (без подпапки private)
                $outPath = $outFilename; // Просто имя файла, без 'public/'

                Log::info("Saving image to public storage: " . $outPath);

                // Сохраняем через временный файл
                $tempPath = storage_path('app/public/' . $outFilename);
                imagepng($image, $tempPath);
                imagedestroy($image);

                Log::info('Image saved successfully to: ' . $tempPath);

                // Проверяем, что файл создан
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

            // Возвращаем только имя файла, без пути 'public/'
            return response()->json([
                'success' => true,
                'restored_image' => $outFilename, // Только имя файла
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
    /* ------------------------ Внутренние LZW методы ------------------------ */

    /**
     * LZW encode: поддерживает вход как строку (будем работать на уровне байтов)
     * или как массив целых 0..255 (пиксели).
     * Возвращает массив кодов (int).
     */
    private function lzwEncode($input, bool $showSteps = false): array
    {
        // Нормализация входа в массив "байтовых символов" (строк длины 1)
        $symbols = [];
        if (is_array($input)) {
            foreach ($input as $v) {
                $val = (int)$v & 0xFF;
                $symbols[] = chr($val);
            }
        } else {
            // строка -> разбиваем по байтам
            // str_split в PHP разделяет по байтам, что удобно для учебного варианта (работаем с байтами).
            $symbols = str_split($input);
        }

        // Инициализация словарей: символ (один байт) -> код 0..255 и обратный
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
            // Сохраняем последние словари (для вывода)
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
                // вывести код текущей строки
                $encoded[] = $symbolToCode[$current];

                // добавить combined в словарь
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

                $current = $c;
            }
        }

        // последний код
        $encoded[] = $symbolToCode[$current];
        if ($showSteps) {
            $this->encodingSteps[] = ['action' => 'final_output', 'output_code' => end($encoded)];
        }

        // Сохраняем последние словари для вывода
        $this->lastSymbolToCode = $symbolToCode;
        $this->lastCodeToSymbol = $codeToSymbol;

        return $encoded;
    }

    /**
     * LZW decode: принимает массив кодов (int) и возвращает байтовую строку.
     */
    /**
     * LZW decode: принимает массив кодов (int) и возвращает байтовую строку.
     */
    /**
     * LZW decode: принимает массив кодов (int) и возвращает байтовую строку.
     */
    /**
     * LZW decode: принимает массив кодов (int) и возвращает байтовую строку.
     * Добавлен третий параметр $robust (по умолчанию false) — если true, при ошибках
     * будет подставляться fallback-байт и декодирование продолжится.
     */
    /**
     * LZW decode: принимает массив кодов (int) и возвращает байтовую строку.
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
            // Инициализация словаря
            $codeToSymbol = [];
            for ($i = 0; $i < 256; $i++) {
                $codeToSymbol[$i] = chr($i);
            }
            $dictSize = 256;

            // Первый код
            $firstCode = (int)$encoded[0];

            // Проверка валидности первого кода
            if ($firstCode < 0 || $firstCode >= $dictSize) {
                if ($robust) {
                    // Используем fallback
                    $firstCode = $firstCode & 0xFF;
                    if ($firstCode >= 256) $firstCode = 0;
                } else {
                    throw new \Exception("Первый код вне диапазона: $firstCode");
                }
            }

            $result = $codeToSymbol[$firstCode] ?? chr($firstCode & 0xFF);
            $prevCode = $firstCode;

            if ($showSteps) {
                $this->decodingSteps[] = [
                    'action' => 'first',
                    'code' => $firstCode,
                    'symbol' => $this->visibleString($result)
                ];
            }

            // Основной цикл декодирования
            for ($i = 1; $i < count($encoded); $i++) {
                $currCode = (int)$encoded[$i];

                try {
                    $currentString = '';

                    // Обработка текущего кода
                    if (isset($codeToSymbol[$currCode])) {
                        $currentString = $codeToSymbol[$currCode];
                    } else if ($currCode == $dictSize) {
                        // Специальный случай: код равен текущему размеру словаря
                        $prevString = $codeToSymbol[$prevCode];
                        $currentString = $prevString . $prevString[0];
                    } else {
                        if ($robust) {
                            // Fallback: используем младший байт
                            $currentString = chr($currCode & 0xFF);
                            $errors[] = "Неизвестный код $currCode на позиции $i";
                        } else {
                            throw new \Exception("Неизвестный код: $currCode на позиции $i");
                        }
                    }

                    // Добавляем декодированную строку к результату
                    $result .= $currentString;

                    // Добавляем новую запись в словарь
                    if ($dictSize < 65536) { // Ограничим размер словаря
                        $prevString = $codeToSymbol[$prevCode];
                        $newEntry = $prevString . $currentString[0];
                        $codeToSymbol[$dictSize] = $newEntry;

                        if ($showSteps) {
                            $this->decodingSteps[] = [
                                'action' => 'add',
                                'new_code' => $dictSize,
                                'new_symbol' => $this->visibleString($newEntry)
                            ];
                        }

                        $dictSize++;
                    }

                    $prevCode = $currCode;

                } catch (\Exception $inner) {
                    if ($robust) {
                        $errors[] = [
                            'index' => $i,
                            'code' => $currCode,
                            'message' => $inner->getMessage()
                        ];
                        // Добавляем fallback байт
                        $result .= chr(0);
                        continue;
                    } else {
                        throw $inner;
                    }
                }
            }

            // Сохраняем словари для отладки
            $this->lastCodeToSymbol = $codeToSymbol;
            $this->lastSymbolToCode = array_flip($codeToSymbol);

            // Логируем нефатальные ошибки
            if (!empty($errors) && $showSteps) {
                foreach ($errors as $error) {
                    $this->decodingSteps[] = [
                        'action' => 'error',
                        'message' => $error['message'] ?? $error
                    ];
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('LZW decode error: ' . $e->getMessage());
            Log::error('Encoded data sample: ' . json_encode(array_slice($encoded, 0, 10)));

            if ($robust) {
                // Попытка восстановления: преобразуем коды напрямую в байты
                $fallback = '';
                foreach ($encoded as $code) {
                    $fallback .= chr($code & 0xFF);
                }
                return $fallback;
            }

            throw new \Exception("Unable to decode input: " . $e->getMessage());
        }
    }



    /* ------------------------ Утилиты ------------------------ */

    /**
     * Рассчитать биты, занятые закодированными кодами.
     * Упрощённый подход: все коды записываются одной шириной = ceil(log2(maxCode+1)), минимум 1 бит.
     * (Можно улучшить до переменной длины кодов по мере роста словаря.)
     */
    private function calculateEncodedBits(array $encoded): int
    {
        if (count($encoded) === 0) {
            return 0;
        }
        $maxCode = max($encoded);
        $bitsPerCode = (int) max(1, ceil(log($maxCode + 1, 2)));
        return count($encoded) * $bitsPerCode;
    }

    /**
     * Для удобного вывода словаря: вернём массив code => readable
     */
    private function formatDictionaryForOutput(): array
    {
        $out = [];
        // предпочитаем lastCodeToSymbol (код => символ)
        if (!empty($this->lastCodeToSymbol)) {
            foreach ($this->lastCodeToSymbol as $code => $symbol) {
                $out[$code] = $this->visibleString($symbol);
            }
        } else {
            // fallback: symbol => code
            foreach ($this->lastSymbolToCode as $symbol => $code) {
                $out[$code] = $this->visibleString($symbol);
            }
        }
        return $out;
    }

    /**
     * Преобразует байтовую строку (или символы) в читаемую форму для отладки:
     * - если символ печатаемый ASCII, показывает 'A' или т.е.
     * - также добавляет ord(...) в скобках.
     */
    private function visibleString(string $s): string
    {
        // Показываем последовательность символов в виде "[65:'A'][66:'B']..." (но не слишком длинно)
        $parts = [];
        $bytes = str_split($s);
        foreach ($bytes as $b) {
            $ord = ord($b);
            // Печатаемый ASCII (32..126) показываем как символ, иначе точка
            $char = ($ord >= 32 && $ord <= 126) ? $b : '.';
            $parts[] = "{$ord}:'{$char}'";
        }
        return implode(',', $parts);
    }
}
