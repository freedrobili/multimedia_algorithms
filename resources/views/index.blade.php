<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №3 - RLE кодирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .result-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .compression-badge {
            font-size: 1.1em;
            padding: 8px 15px;
        }
        .rle-visual {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        .pixel {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin: 1px;
            border: 1px solid #f0f0f0;
        }
        .pixel-0 { background-color: black; }
        .pixel-1 { background-color: white; }
        .sequence-item {
            display: inline-block;
            padding: 2px 6px;
            margin: 2px;
            border-radius: 3px;
            font-size: 0.8em;
            border: 1px solid #ddd;
        }
        .sequence-count {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .sequence-value {
            background-color: #fff3e0;
            color: #e65100;
        }
        .preview-container {
            max-width: 300px;
            max-height: 300px;
            overflow: hidden;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            margin: 10px 0;
        }
        .preview-image {
            width: 100%;
            height: auto;
        }
        .progress {
            height: 25px;
            margin: 10px 0;
        }
        .comparison-table {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Лабораторная работа №3</h5>
                    <h6 class="mb-0">«Реализация алгоритма кодирования повторов (RLE)»</h6>
                </div>
            </div>

            <!-- Кодирование текста -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Кодирование текста</h5>
                </div>
                <div class="card-body">
                    <form id="textForm">
                        @csrf
                        <div class="mb-3">
                            <label for="input_text" class="form-label">Введите текст для кодирования:</label>
                            <textarea class="form-control" id="input_text" name="input_text" rows="3"
                                      placeholder="Введите последовательность символов (например: AAABBBBBA)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Закодировать</button>
                    </form>

                    <div id="textResults" style="display: none;">
                        <div class="mt-4">
                            <h6>Результаты кодирования:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Исходная последовательность:</strong>
                                    <div class="result-box" id="originalResult"></div>

                                    <strong class="mt-3">Закодированная последовательность:</strong>
                                    <div class="result-box" id="encodedResult"></div>

                                    <div class="mt-2">
{{--                                        <strong>Визуализация RLE:</strong>--}}
                                        <div id="rleVisualization" class="rle-visual"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Декодированная последовательность:</strong>
                                    <div class="result-box" id="decodedResult"></div>

                                    <div class="stats mt-3">
                                        <strong>Сравнение эффективности:</strong>
                                        <div class="comparison-table mt-2">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Параметр</th>
                                                    <th>До сжатия</th>
                                                    <th>После сжатия</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>Длина (символов)</td>
                                                    <td id="originalLength">0</td>
                                                    <td id="encodedLength">0</td>
                                                </tr>
                                                <tr>
                                                    <td>Биты</td>
                                                    <td id="originalBits">0</td>
                                                    <td id="encodedBits">0</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Степень компрессии:</strong>
                                            <span id="compressionRatio" class="badge bg-success compression-badge">0%</span>
                                        </div>

                                        <div class="progress">
                                            <div id="compressionBar" class="progress-bar bg-success"
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Кодирование изображения -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Кодирование монохромного изображения</h5>
                </div>
                <div class="card-body">
                    <form id="imageForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="image" class="form-label">Выберите изображение:</label>
                            <input class="form-control" type="file" id="image" name="image" accept="image/*">
                            <div class="form-text">Поддерживаемые форматы: PNG, JPG, JPEG, BMP</div>
                        </div>
                        <button type="submit" class="btn btn-success">Закодировать изображение</button>
                    </form>

                    <div id="imageResults" style="display: none;">
                        <div class="mt-4">
                            <h6>Результаты кодирования изображения:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Информация об изображении:</strong>
                                    <div class="result-box">
                                        Размер: <span id="imageSize"></span><br>
                                        Ширина: <span id="imageWidth"></span> px<br>
                                        Высота: <span id="imageHeight"></span> px<br>
                                        Всего пикселей: <span id="totalPixels"></span>
                                    </div>

                                    <div class="preview-container">
                                        <strong>Предпросмотр:</strong>
                                        <img id="imagePreview" class="preview-image" src="" alt="Preview">
                                    </div>

                                    <strong>Пример монохромного преобразования:</strong>
                                    <div id="monochromePreview" class="rle-visual"></div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Закодированное изображение (RLE):</strong>
                                    <div class="result-box">
                                        <div id="encodedImage"></div>
                                        <div class="d-flex align-items-center mt-2">
                                            <button id="showAllBtn" class="btn btn-sm btn-outline-primary me-2">Показать всё</button>
                                            <button id="downloadBtn" class="btn btn-sm btn-outline-secondary">Скачать</button>
                                        </div>

                                        <small class="text-muted">Показано: <span id="shownChars">0</span> из <span id="totalChars">0</span> символов</small>
                                    </div>

                                    <div class="stats mt-3">
                                        <strong>Статистика сжатия изображения:</strong>
                                        <div class="comparison-table mt-2">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Параметр</th>
                                                    <th>До сжатия</th>
                                                    <th>После сжатия</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>Размер данных</td>
                                                    <td id="imageOriginalBits">0 бит</td>
                                                    <td id="imageEncodedBits">0 бит</td>
                                                </tr>
                                                <tr>
                                                    <td>Эффективность</td>
                                                    <td colspan="2">
                                                        Экономия: <span id="savedBits">0</span> бит
                                                        (<span id="imageCompressionRatio">0</span>%)
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="progress">
                                            <div id="imageCompressionBar" class="progress-bar bg-info"
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>

                                        <div class="mt-2">
                                            <strong>Анализ RLE последовательности:</strong>
                                            <div id="rleAnalysis" class="small mt-1"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Обработка формы текста
    document.getElementById('textForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const inputText = document.getElementById('input_text').value;

        fetch('{{ route("rle.index") }}/encode-text', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Основные данные
                document.getElementById('originalResult').textContent = inputText;
                document.getElementById('encodedResult').textContent = data.encoded;
                document.getElementById('decodedResult').textContent = data.decoded;

                // Статистика
                document.getElementById('originalLength').textContent = inputText.length;
                document.getElementById('encodedLength').textContent = data.encoded.length;
                document.getElementById('originalBits').textContent = data.original_bits;
                document.getElementById('encodedBits').textContent = data.encoded_bits;
                document.getElementById('compressionRatio').textContent = data.compression_ratio + '%';

                // Визуализация прогресса
                const compressionBar = document.getElementById('compressionBar');
                compressionBar.style.width = Math.min(data.compression_ratio, 100) + '%';

                // Визуализация RLE
                visualizeRLE(data.encoded);

                document.getElementById('textResults').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при кодировании текста');
            });
    });

    // Обработка формы изображения
    document.getElementById('imageForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const fileInput = document.getElementById('image');

        // Показываем превью
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(fileInput.files[0]);
        }

        fetch('{{ route("rle.index") }}/encode-image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Основные данные
                document.getElementById('imageWidth').textContent = data.width;
                document.getElementById('imageHeight').textContent = data.height;
                document.getElementById('imageSize').textContent = data.width + '×' + data.height;
                document.getElementById('totalPixels').textContent = (data.width * data.height).toLocaleString();

                // Ограничиваем вывод RLE
                // const maxDisplayLength = 500;
                // const fullEncoded = data.encoded_image;
                // const displayText = fullEncoded.length > maxDisplayLength
                //     ? fullEncoded.substring(0, maxDisplayLength) + '...'
                //     : fullEncoded;
                const fullEncoded = data.encoded_image;
                const maxDisplayLength = 500; // количество символов в сокращенном виде
                const shortText = fullEncoded.length > maxDisplayLength
                    ? fullEncoded.substring(0, maxDisplayLength) + '...'
                    : fullEncoded;

                document.getElementById('encodedImage').textContent = shortText;
                document.getElementById('shownChars').textContent = Math.min(fullEncoded.length, maxDisplayLength).toLocaleString();
                document.getElementById('totalChars').textContent = fullEncoded.length.toLocaleString();

// Кнопка Показать всё / Свернуть
                const showAllBtn = document.getElementById('showAllBtn');
                let showingAll = false;
                showAllBtn.onclick = function() {
                    if (!showingAll) {
                        document.getElementById('encodedImage').textContent = fullEncoded;
                        document.getElementById('shownChars').textContent = fullEncoded.length.toLocaleString();
                        showAllBtn.textContent = 'Свернуть';
                        showingAll = true;
                    } else {
                        document.getElementById('encodedImage').textContent = shortText;
                        document.getElementById('shownChars').textContent = Math.min(fullEncoded.length, maxDisplayLength).toLocaleString();
                        showAllBtn.textContent = 'Показать всё';
                        showingAll = false;
                    }
                };

// Кнопка Скачать (сохранить как .txt)
                const downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.onclick = function() {
                    const blob = new Blob([fullEncoded], { type: 'text/plain;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'rle_encoded.txt';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                };


                // // const displayText = fullEncoded;
                // document.getElementById('encodedImage').textContent = displayText;
                // document.getElementById('shownChars').textContent = Math.min(fullEncoded.length, maxDisplayLength).toLocaleString();
                // document.getElementById('totalChars').textContent = fullEncoded.length.toLocaleString();

                // Статистика
                document.getElementById('imageOriginalBits').textContent = data.original_bits.toLocaleString() + ' бит';
                document.getElementById('imageEncodedBits').textContent = data.encoded_bits.toLocaleString() + ' бит';
                document.getElementById('imageCompressionRatio').textContent = data.compression_ratio;

                const savedBits = data.original_bits - data.encoded_bits;
                document.getElementById('savedBits').textContent = savedBits.toLocaleString();

                // Визуализация прогресса
                const compressionBar = document.getElementById('imageCompressionBar');
                compressionBar.style.width = Math.min(data.compression_ratio, 100) + '%';

                // Анализ RLE
                analyzeRLE(fullEncoded);

                document.getElementById('imageResults').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при кодировании изображения');
            });
    });

    // Универсальная визуализация RLE (поддерживает value:count и count+value)
    function visualizeRLE(encodedString) {
        const container = document.getElementById('rleVisualization');
        container.innerHTML = '';

        // Нормализуем: заменим запятые/переносы на пробелы и разобьём по пробелам
        const tokens = encodedString.replace(/\s+/g, ' ').trim().split(' ').filter(Boolean);

        tokens.forEach(token => {
            // Попробуем парсить оба формата
            let value = null;
            let count = null;

            // формат value:count (например "0:119380" или "1:21")
            let m = token.match(/^([01])[:](\d+)$/);
            if (m) {
                value = m[1];
                count = parseInt(m[2], 10);
            } else {
                // формат count+value (например "1193800" или "21 1" без двоеточия)
                m = token.match(/^(\d+)([01])$/);
                if (m) {
                    count = parseInt(m[1], 10);
                    value = m[2];
                }
            }

            if (value === null || !Number.isFinite(count) || count <= 0) {
                // некорректный токен — пропускаем
                return;
            }

            // Создаём визуальные элементы (покажем один блок count и один блок value)
            const countSpan = document.createElement('span');
            countSpan.className = 'sequence-item sequence-count';
            countSpan.textContent = count;
            countSpan.title = `Повторов: ${count}`;

            const charSpan = document.createElement('span');
            charSpan.className = 'sequence-item sequence-value';
            charSpan.textContent = value;
            charSpan.title = `Символ: ${value}`;

            container.appendChild(countSpan);
            container.appendChild(charSpan);
            container.appendChild(document.createTextNode(' '));
        });
    }

    // Универсальный парсер/анализатор RLE (поддерживает оба формата)
    // возвращает статистику и набор корректных серий
    function parseRLEtoSequences(encodedString) {
        const tokens = encodedString.replace(/\s+/g, ' ').trim().split(' ').filter(Boolean);
        const sequences = [];

        tokens.forEach(token => {
            let m = token.match(/^([01])[:](\d+)$/);
            if (m) {
                const value = m[1];
                const count = parseInt(m[2], 10);
                if (Number.isFinite(count) && count > 0) sequences.push({count, char: value});
                return;
            }
            m = token.match(/^(\d+)([01])$/);
            if (m) {
                const count = parseInt(m[1], 10);
                const value = m[2];
                if (Number.isFinite(count) && count > 0) sequences.push({count, char: value});
                return;
            }
            // если токен не соответствует ни одному формату — пропускаем
        });

        return sequences;
    }

    function analyzeRLE(encodedString) {
        const analysisContainer = document.getElementById('rleAnalysis');

        const sequences = parseRLEtoSequences(encodedString);

        if (sequences.length === 0) {
            analysisContainer.innerHTML = `<div>Нет корректных серий для анализа.</div>`;
            createMonochromePreview([]);
            return;
        }

        let totalSequences = sequences.length;
        let maxSequence = 0;
        let minSequence = Number.POSITIVE_INFINITY;
        let sum = 0;

        sequences.forEach(seq => {
            maxSequence = Math.max(maxSequence, seq.count);
            minSequence = Math.min(minSequence, seq.count);
            sum += seq.count;
        });

        // Если minSequence остался Infinity — значит нет корректных серий
        if (!Number.isFinite(minSequence)) minSequence = 0;

        const avgSequence = sum / totalSequences;

        analysisContainer.innerHTML = `
        <div>Всего серий: <strong>${totalSequences.toLocaleString()}</strong></div>
        <div>Макс. длина серии: <strong>${maxSequence.toLocaleString()}</strong></div>
        <div>Мин. длина серии: <strong>${minSequence.toLocaleString()}</strong></div>
        <div>Средняя длина серии: <strong>${avgSequence.toFixed(1)}</strong></div>
        <div>Эффективность: <strong>${(avgSequence > 2 ? 'Высокая' : 'Низкая')}</strong></div>
    `;

        // Превью первых 50 серий
        createMonochromePreview(sequences.slice(0, 50));
    }


    // Функция для создания превью монохромного изображения
    function createMonochromePreview(sequences) {
        const container = document.getElementById('monochromePreview');
        container.innerHTML = '<strong>Мини-превью (первые 50 серий):</strong><br>';

        const maxPixelsPerRow = 40; // количество пикселей в одной строке
        let rowDiv = document.createElement('div');
        rowDiv.style.cssText = 'line-height: 1; margin-top: 2px; white-space: nowrap;';
        let pixelInRow = 0;

        // Находим максимальную длину серии для масштабирования
        const maxSeriesLength = Math.max(...sequences.map(seq => seq.count));
        const maxPixels = 200; // ограничение на общее количество пикселей
        const scale = maxSeriesLength > maxPixels ? maxPixels / maxSeriesLength : 1;

        sequences.forEach(seq => {
            const scaledCount = Math.max(1, Math.floor(seq.count * scale));

            for (let i = 0; i < scaledCount; i++) {
                const pixel = document.createElement('span');
                pixel.className = `pixel pixel-${seq.char}`;
                pixel.title = `Цвет: ${seq.char === '1' ? 'белый' : 'черный'}`;
                rowDiv.appendChild(pixel);
                pixelInRow++;

                if (pixelInRow >= maxPixelsPerRow) {
                    container.appendChild(rowDiv);
                    rowDiv = document.createElement('div');
                    rowDiv.style.cssText = 'line-height: 1; margin-top: 2px; white-space: nowrap;';
                    pixelInRow = 0;
                }
            }
        });

        if (pixelInRow > 0) container.appendChild(rowDiv);

        // Легенда
        const legend = document.createElement('div');
        legend.className = 'small mt-2';
        legend.innerHTML = `
        <span class="pixel pixel-0"></span> - Черный (0)
        <span class="pixel pixel-1 ms-2"></span> - Белый (1)
    `;
        container.appendChild(legend);
    }


</script>
</body>
</html>
