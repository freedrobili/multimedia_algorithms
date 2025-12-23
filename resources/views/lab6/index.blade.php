<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Лабораторная работа №6 - DCT</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- CSRF token для JS -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Подключаем Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
        }

        .header-section {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .header-section h1 {
            font-weight: 600;
            font-size: 2.2rem;
            margin-bottom: 5px;
        }

        .header-section .lead {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Стили для секций заданий */
        .task-section {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #57568c;
        }

        .task-section h2 {
            color: #57568c;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.4rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Стили для кнопок */
        .btn-dct {
            background-color: #57568c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-dct:hover {
            background-color: #454472;
            color: white;
            transform: translateY(-1px);
        }

        .btn-secondary-dct {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-secondary-dct:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-1px);
        }

        .btn-success-dct {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-success-dct:hover {
            background-color: #218838;
            color: white;
            transform: translateY(-1px);
        }

        /* Стили для canvas графиков */
        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* Стили для загрузки изображений */
        .image-upload-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            border: 2px dashed #dee2e6;
        }

        .image-preview {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        /* Стили для результатов */
        .results-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }

        .results-container h3 {
            color: #57568c;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        /* Стили для информации о сигнале */
        .signal-info {
            background-color: #e8f4fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 0.9rem;
            border-left: 3px solid #4facfe;
        }

        /* Стили для таблиц метрик */
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .metrics-table th, .metrics-table td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .metrics-table th {
            background-color: #f8f9fa;
            color: #57568c;
            font-weight: 600;
        }
        /* Добавьте в стили */
        .spectrum-legend {
            display: flex;
            align-items: center;
            margin-top: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .color-scale {
            display: flex;
            height: 20px;
            width: 200px;
            margin-right: 15px;
            background: linear-gradient(90deg,
            #000080 0%,    /* Темно-синий */
            #0000ff 20%,   /* Синий */
            #0080ff 40%,   /* Голубой */
            #00ffff 60%,   /* Бирюзовый */
            #00ff80 70%,   /* Зеленый-бирюзовый */
            #00ff00 75%,   /* Зеленый */
            #80ff00 80%,   /* Желто-зеленый */
            #ffff00 85%,   /* Желтый */
            #ff8000 90%,   /* Оранжевый */
            #ff0000 95%,   /* Красный */
            #800000 100%   /* Темно-красный */
            );
        }

        .image-preview {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .image-preview:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .energy-badge {
            font-size: 1rem;
            padding: 0.35em 0.65em;
            margin: 0 5px;
        }

        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h6 {
            color: white;
            margin-bottom: 10px;
        }

        .bi {
            margin-right: 5px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .task-section {
                padding: 20px;
            }

            .btn-dct, .btn-secondary-dct, .btn-success-dct {
                display: block;
                width: 100%;
                margin-bottom: 10px;
            }

            .header-section h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <!-- Заголовок -->
    <div class="header-section text-center">
        <h1>Лабораторная работа №6</h1>
        <p class="lead">Дискретное косинусное преобразование (DCT)</p>
    </div>

    <!-- Задание 1 -->
    <div class="task-section">
        <h2>Задание 1: Прямое и обратное DCT для 1D сигнала</h2>
        <div class="mb-3">
            <button class="btn-dct" onclick="generateSignal()">Сгенерировать сигнал</button>
            <button class="btn-dct" onclick="applyDCT()">Применить DCT</button>
            <button class="btn-dct" onclick="applyIDCT()">Применить IDCT</button>
            <button class="btn-secondary-dct" onclick="clearSignal()">Очистить</button>
        </div>

        <div id="signalInfo" class="signal-info" style="display: none;">
            Длина сигнала: <span id="signalLength">0</span> |
            Максимальное значение: <span id="signalMax">0</span> |
            Минимальное значение: <span id="signalMin">0</span>
        </div>

        <div class="chart-container">
            <canvas id="signalChart" height="300"></canvas>
        </div>

        <div id="task1Results" class="results-container" style="display: none;">
            <h3>Результаты преобразования</h3>
            <table class="metrics-table">
                <thead>
                <tr>
                    <th>Параметр</th>
                    <th>Значение</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Ошибка восстановления (ε)</td>
                    <td id="restorationError">0</td>
                </tr>
                <tr>
                    <td>Время DCT (мс)</td>
                    <td id="dctTime">0</td>
                </tr>
                <tr>
                    <td>Время IDCT (мс)</td>
                    <td id="idctTime">0</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Задание 2 -->
    <div class="task-section">
        <h2>Задание 2: Обнуление высокочастотных коэффициентов</h2>
        <p class="mb-3">Сначала сгенерируйте сигнал и примените DCT (Задание 1), затем обнулите коэффициенты:</p>
        <div class="mb-3">
            <button class="btn-dct" onclick="zeroHighFreq(50)">Обнулить 50%</button>
            <button class="btn-dct" onclick="zeroHighFreq(80)">Обнулить 80%</button>
            <button class="btn-dct" onclick="zeroHighFreq(90)">Обнулить 90%</button>
        </div>

        <div class="chart-container">
            <canvas id="zeroChart" height="300"></canvas>
        </div>

        <div id="task2Results" class="results-container" style="display: none;">
            <h3>Сравнение результатов обнуления</h3>
            <table class="metrics-table">
                <thead>
                <tr>
                    <th>% обнуленных коэффициентов</th>
                    <th>Ошибка восстановления</th>
                    <th>Энергия сохранена</th>
                </tr>
                </thead>
                <tbody id="zeroResultsBody">
                <!-- Данные будут добавлены динамически -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Задание 3 -->
    <!-- В index.blade.php в секции Задания 3 после загрузки изображения добавьте: -->
    <div class="task-section">
        <h2>Задание 3: 2D DCT изображения</h2>
        <div class="image-upload-container">
            <div class="mb-3">
                <label for="imageUpload" class="form-label">Выберите изображение:</label>
                <input class="form-control" type="file" id="imageUpload" accept="image/*">
            </div>
            <button class="btn-success-dct" onclick="uploadImage()">Загрузить изображение</button>
            <button class="btn-dct" onclick="apply2DDCT()" id="applyDCTBtn" style="display: none;">Применить 2D DCT</button>
        </div>

        <div id="imageContainer" class="mt-3">
            <!-- Здесь будут отображаться изображения -->
        </div>

        <div id="task3Results" class="results-container" style="display: none;">
            <h3>Результаты 2D DCT</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Исходное изображение</h5>
                    <img id="originalImgPreview" class="image-preview" src="" alt="Оригинал" style="max-width: 100%;">
                    <p class="text-center mt-2"><small>Градации серого</small></p>
                </div>
                <div class="col-md-6">
                    <h5>Спектр DCT (логарифмическая шкала)</h5>
                    <img id="dctSpectrumImg" class="image-preview" src="" alt="Спектр DCT" style="max-width: 100%;">
                    <p class="text-center mt-2"><small>Энергия: <span id="energyPercent">0%</span> в 1% коэффициентов</small></p>
                </div>
            </div>

            <div class="mt-4">
                <h5>Анализ распределения энергии</h5>
                <div id="energyDistributionChart" style="height: 200px; width: 100%;"></div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Информация о DCT:</h6>
                        <ul>
                            <li>Размер изображения: <span id="imageSize">0x0</span></li>
                            <li>Количество коэффициентов: <span id="coefficientsCount">0</span></li>
                            <li>DC коэффициент: <span id="dcValue">0</span></li>
                            <li>Макс. коэффициент: <span id="maxCoefficient">0</span></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Распределение энергии:</h6>
                        <ul>
                            <li>Верхний левый квадрант (низкие частоты): <span id="lowFreqEnergy">0%</span></li>
                            <li>Центральная область: <span id="midFreqEnergy">0%</span></li>
                            <li>Нижний правый квадрант (высокие частоты): <span id="highFreqEnergy">0%</span></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-3">
                    <h6>Анализ:</h6>
                    <p id="energyAnalysis">Энергия в DCT-спектре обычно сосредоточена в левом верхнем углу (низкие частоты), что соответствует плавным изменениям яркости. Высокочастотные коэффициенты (правый нижний угол) обычно содержат мало энергии.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Задание 4 -->
    <div class="task-section">
        <h2>Задание 4: Блочное DCT 8x8 (JPEG модель)</h2>
        <p class="mb-3">Сначала загрузите изображение (Задание 3), затем примените блочное преобразование:</p>
        <div class="mb-3">
            <button class="btn-success-dct" onclick="applyBlockDCT()">Применить блочное DCT</button>
            <select id="quantizationStrength" class="form-select d-inline-block w-auto">
                <option value="1">Низкое квантование</option>
                <option value="2" selected>Среднее квантование</option>
                <option value="3">Высокое квантование</option>
            </select>
        </div>

        <div id="task4Results" class="results-container" style="display: none;">
            <h3>Результаты JPEG-сжатия</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Оригинальное изображение</h5>
                    <img id="originalImage" class="image-preview" src="" alt="Оригинал">
                </div>
                <div class="col-md-6">
                    <h5>После сжатия</h5>
                    <img id="compressedImage" class="image-preview" src="" alt="После сжатия">
                </div>
            </div>

            <div class="mt-4">
                <h5>Метрики качества</h5>
                <table class="metrics-table">
                    <thead>
                    <tr>
                        <th>Метрика</th>
                        <th>Значение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>PSNR (дБ)</td>
                        <td id="psnrValue">0</td>
                    </tr>
                    <tr>
                        <td>Размер файла до</td>
                        <td id="originalSize">0 КБ</td>
                    </tr>
                    <tr>
                        <td>Размер файла после</td>
                        <td id="compressedSize">0 КБ</td>
                    </tr>
                    <tr>
                        <td>Сжатие</td>
                        <td id="compressionRatio">0%</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <h5>Артефакты сжатия</h5>
                <div id="artifactsInfo">
                    <!-- Информация об артефактах -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let originalSignal = [];
    let dctCoeffs = [];
    let signalChart = null;
    let zeroChart = null;
    let uploadedPixels = null;
    let dctCoefficients = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Задание 1: 1D DCT
    async function generateSignal() {
        const res = await fetch('/lab6/generate-signal', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        originalSignal = data.signal;

        // Обновляем информацию о сигнале
        document.getElementById('signalInfo').style.display = 'block';
        document.getElementById('signalLength').textContent = originalSignal.length;
        document.getElementById('signalMax').textContent = Math.max(...originalSignal).toFixed(4);
        document.getElementById('signalMin').textContent = Math.min(...originalSignal).toFixed(4);

        plotSignal('signalChart', originalSignal, 'Исходный сигнал');
    }

    async function applyDCT() {
        const startTime = performance.now();
        const res = await fetch('/lab6/dct-1d', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ signal: originalSignal })
        });
        const data = await res.json();
        dctCoeffs = data.dct;
        const endTime = performance.now();

        document.getElementById('dctTime').textContent = (endTime - startTime).toFixed(2);
        document.getElementById('task1Results').style.display = 'block';

        plotSignal('signalChart', dctCoeffs, 'DCT коэффициенты');
    }

    async function applyIDCT() {
        const startTime = performance.now();
        const res = await fetch('/lab6/idct-1d', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ dct: dctCoeffs })
        });
        const data = await res.json();
        const endTime = performance.now();

        document.getElementById('idctTime').textContent = (endTime - startTime).toFixed(2);

        // Вычисляем ошибку восстановления
        let error = 0;
        for (let i = 0; i < originalSignal.length; i++) {
            error += Math.pow(originalSignal[i] - data.signal[i], 2);
        }
        document.getElementById('restorationError').textContent = error.toFixed(6);

        plotSignal('signalChart', data.signal, 'Восстановленный сигнал');
    }

    // Задание 2: Обнуление коэффициентов
    async function zeroHighFreq(percent) {
        const res = await fetch('/lab6/dct-zero-high-freq', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ dct: dctCoeffs, percent: percent })
        });
        const data = await res.json();

        plotSignal('zeroChart', data.dct_zeroed, `Обнулено ${percent}% высоких частот`);

        // Обновляем таблицу результатов
        document.getElementById('task2Results').style.display = 'block';
        let tableBody = document.getElementById('zeroResultsBody');

        // Вычисляем сохраненную энергию
        let totalEnergy = 0;
        let savedEnergy = 0;
        for (let i = 0; i < dctCoeffs.length; i++) {
            totalEnergy += Math.pow(dctCoeffs[i], 2);
            if (i < dctCoeffs.length * (100 - percent) / 100) {
                savedEnergy += Math.pow(dctCoeffs[i], 2);
            }
        }
        let energyPercent = (savedEnergy / totalEnergy * 100).toFixed(1);

        // Добавляем строку в таблицу
        let newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${percent}%</td>
            <td>${calculateReconstructionError(data.dct_zeroed)}</td>
            <td>${energyPercent}%</td>
        `;
        tableBody.appendChild(newRow);
    }

    // Задание 3: 2D DCT изображения
    async function uploadImage() {
        const fileInput = document.getElementById('imageUpload');
        if (!fileInput.files[0]) {
            alert('Пожалуйста, выберите файл изображения');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileInput.files[0]);

        try {
            const res = await fetch('/lab6/upload-image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Ошибка сервера');
            }

            if (data.error) {
                throw new Error(data.error);
            }

            // Сохраняем пиксели для последующей обработки
            uploadedPixels = data.pixels;
            imageWidth = data.width;
            imageHeight = data.height;

            document.getElementById('imageContainer').innerHTML = `
                <div class="alert alert-success">${data.message || 'Изображение загружено'}: ${data.width} × ${data.height} пикселей</div>
                <div class="text-center">
                    <img src="${data.path}" id="uploadedImage" class="image-preview" alt="Загруженное изображение" style="max-width: 300px;">
                </div>
                <div class="text-center mt-3">
                    <button class="btn-dct" onclick="apply2DDCT()" id="applyDCTBtn">Применить 2D DCT к этому изображению</button>
                </div>
            `;

        } catch (error) {
            console.error('Ошибка загрузки изображения:', error);
            document.getElementById('imageContainer').innerHTML = `
                <div class="alert alert-danger">Ошибка: ${error.message}</div>
            `;
        }
    }

    async function apply2DDCT() {
        if (!uploadedPixels) {
            alert('Сначала загрузите изображение');
            return;
        }

        try {
            // Показываем индикатор загрузки
            const task3Results = document.getElementById('task3Results');
            task3Results.style.display = 'block';
            task3Results.innerHTML = '<div class="alert alert-info">Применение 2D DCT... это может занять несколько секунд</div>';

            // Получаем размеры из загруженного изображения
            const img = document.getElementById('uploadedImage');
            if (!img) {
                throw new Error('Изображение не найдено');
            }

            // Используем размеры из данных загрузки
            const width = uploadedPixels[0].length;
            const height = uploadedPixels.length;

            console.log(`Размеры изображения: ${width}x${height}`);

            // Ограничиваем размер для производительности
            const maxSize = 256;
            let scale = 1;

            if (width > maxSize || height > maxSize) {
                scale = Math.min(maxSize / width, maxSize / height);
            }

            const scaledWidth = Math.floor(width * scale);
            const scaledHeight = Math.floor(height * scale);

            console.log(`Масштабирование до: ${scaledWidth}x${scaledHeight}`);

            // Масштабируем пиксели
            const scaledPixels = [];
            for (let y = 0; y < scaledHeight; y++) {
                const row = [];
                for (let x = 0; x < scaledWidth; x++) {
                    const origX = Math.floor(x / scale);
                    const origY = Math.floor(y / scale);
                    row.push(uploadedPixels[origY][origX] || 0);
                }
                scaledPixels.push(row);
            }

            console.log(`Применяем 2D DCT к изображению ${scaledWidth}x${scaledHeight}`);

            // Отправляем запрос на 2D DCT с ВСЕМИ необходимыми параметрами
            const res = await fetch('/lab6/dct2d', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    pixels: scaledPixels,
                    width: scaledWidth,     // Добавляем ширину
                    height: scaledHeight    // Добавляем высоту
                })
            });

            if (!res.ok) {
                const text = await res.text();
                throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
            }

            const data = await res.json();

            if (data.error) {
                throw new Error(data.error);
            }

            console.log('Ответ от сервера:', data);

            // Сохраняем коэффициенты DCT (проверяем оба возможных имени поля)
            dctCoefficients = data.dct2d || data.dct2D || data.dct_coefficients || data.dct2d_coefficients;

            if (!dctCoefficients) {
                console.warn('Нет DCT коэффициентов в ответе:', data);
                // Если нет коэффициентов, но есть спектр, показываем хотя бы его
                if (data.spectrum_path) {
                    await showDCTResults(data, scaledWidth, scaledHeight);
                    return;
                }
                throw new Error('Не удалось получить коэффициенты DCT из ответа сервера');
            }

            // Создаем визуализацию спектра
            await createDctVisualization(dctCoefficients, scaledWidth, scaledHeight, data);

            // Анализируем распределение энергии
            setTimeout(() => {
                analyzeEnergyDistribution(dctCoefficients, scaledWidth, scaledHeight);
            }, 100);

        } catch (error) {
            console.error('Ошибка при применении 2D DCT:', error);
            document.getElementById('task3Results').innerHTML = `
            <div class="alert alert-danger">
                <h5>Ошибка</h5>
                <p>${error.message}</p>
                <p>Попробуйте использовать изображение меньшего размера.</p>
                <button class="btn btn-sm btn-secondary" onclick="trySimpleDCT()">Попробовать упрощенный вариант</button>
            </div>
        `;
        }
    }

    // В функции createDctVisualization добавьте:
    async function createDctVisualization(dctCoeffs, width, height, serverData = null) {
        try {
            let spectrumUrl;
            let heatmapUrl = null;

            // Если сервер вернул несколько спектров
            if (serverData && serverData.spectrum_paths) {
                spectrumUrl = serverData.main_spectrum ||
                    serverData.spectrum_paths.color ||
                    serverData.spectrum_paths.heatmap ||
                    serverData.spectrum_paths.simple;

                if (serverData.spectrum_paths.heatmap) {
                    heatmapUrl = serverData.spectrum_paths.heatmap;
                }
            } else if (serverData && serverData.spectrum_path) {
                spectrumUrl = serverData.spectrum_path;
            } else {
                spectrumUrl = await createDctSpectrumLocal(dctCoeffs, width, height);
            }

            // Обновляем интерфейс
            const task3Results = document.getElementById('task3Results');
            const uploadedImage = document.getElementById('uploadedImage');

            let spectrumHTML = `
            <h5>Спектр DCT (логарифмическая шкала)</h5>
            <img src="${spectrumUrl}" id="dctSpectrumImg" class="image-preview"
                 alt="Спектр DCT" style="max-width: 100%; max-height: 300px; border: 1px solid #ddd;">
            <div class="mt-2 text-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Цветовая шкала: синий (малые значения) → красный (большие значения)
                </small>
            </div>
        `;

            if (heatmapUrl) {
                spectrumHTML += `
                <div class="mt-3">
                    <h6>Тепловая карта (альтернативный вид)</h6>
                    <img src="${heatmapUrl}" class="image-preview"
                         alt="Тепловая карта DCT" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd;">
                </div>
            `;
            }

            task3Results.innerHTML = `
            <h3>Результаты 2D DCT</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Исходное изображение</h5>
                    <img src="${uploadedImage.src}" id="originalImgPreview" class="image-preview"
                         alt="Оригинал" style="max-width: 100%; max-height: 300px;">
                    <p class="text-center mt-2">
                        <small>${width} × ${height} пикселей, градации серого</small>
                    </p>
                </div>
                <div class="col-md-6">
                    ${spectrumHTML}
                    <p class="text-center mt-2">
                        <small>
                            <strong>Энергия:</strong>
                            <span id="energyPercent" class="badge bg-primary">
                                ${serverData?.energy_analysis?.energy_in_top_1_percent || '0'}%
                            </span>
                            в 1% коэффициентов
                        </small>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-lightbulb"></i> Как читать спектр DCT:</h6>
                        <ul class="mb-0">
                            <li><strong>Левый верхний угол (DC коэффициент)</strong> - средняя яркость изображения (самое яркое)</li>
                            <li><strong>Ось X</strong> - горизонтальные частоты (влево → низкие, вправо → высокие)</li>
                            <li><strong>Ось Y</strong> - вертикальные частоты (вверх → низкие, вниз → высокие)</li>
                            <li><strong>Яркие/красные области</strong> - важные частотные компоненты</li>
                            <li><strong>Темные/синие области</strong> - незначительные частоты</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-4" id="energyAnalysisContent">
                <h5>Анализ распределения энергии</h5>
                <div id="energyAnalysis" style="min-height: 100px;">
                    <div class="alert alert-info">
                        <p><i class="bi bi-hourglass-split"></i> Вычисление распределения энергии...</p>
                    </div>
                </div>
            </div>
        `;

            // Автоматически запускаем анализ
            setTimeout(() => {
                analyzeEnergyDistribution(dctCoeffs, width, height);
            }, 500);

        } catch (error) {
            console.error('Ошибка создания визуализации:', error);
            showSimpleResults(width, height);
        }
    }

    function updateEnergyAnalysisUI(energyAnalysis, dctCoeffs, width, height) {
        const energyPercentEl = document.getElementById('energyPercent');
        const analysisContent = document.getElementById('energyAnalysisContent');

        if (!energyPercentEl || !analysisContent) return;

        energyPercentEl.textContent = `${energyAnalysis.energy_in_top_1_percent}%`;

        // Вычисляем распределение по квадрантам
        let lowFreqEnergy = 0;
        let midFreqEnergy = 0;
        let highFreqEnergy = 0;
        const totalEnergy = energyAnalysis.total_energy;

        const quarterWidth = Math.floor(width / 4);
        const quarterHeight = Math.floor(height / 4);

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const coeff = dctCoeffs[y][x];
                const energy = coeff * coeff;

                if (x < quarterWidth && y < quarterHeight) {
                    lowFreqEnergy += energy;
                } else if (x >= width * 3/4 && y >= height * 3/4) {
                    highFreqEnergy += energy;
                } else {
                    midFreqEnergy += energy;
                }
            }
        }

        analysisContent.innerHTML = `
        <h5>Анализ распределения энергии</h5>
        <div style="position: relative; height: 200px; width: 100%;">
            <canvas id="energyChart"></canvas>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <h6>Информация о DCT:</h6>
                <ul>
                    <li>Размер изображения: <span id="imageSize">${width} × ${height}</span></li>
                    <li>Количество коэффициентов: <span id="coefficientsCount">${width * height}</span></li>
                    <li>DC коэффициент: <span id="dcValue">${energyAnalysis.dc_coefficient?.toFixed(2) || '0.00'}</span></li>
                    <li>Общая энергия: <span id="totalEnergy">${energyAnalysis.total_energy?.toFixed(2) || '0.00'}</span></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Распределение энергии:</h6>
                <ul>
                    <li>Верхний левый квадрант (низкие частоты): <span id="lowFreqEnergy">${totalEnergy > 0 ? ((lowFreqEnergy / totalEnergy) * 100).toFixed(1) : '0.0'}%</span></li>
                    <li>Центральная область: <span id="midFreqEnergy">${totalEnergy > 0 ? ((midFreqEnergy / totalEnergy) * 100).toFixed(1) : '0.0'}%</span></li>
                    <li>Нижний правый квадрант (высокие частоты): <span id="highFreqEnergy">${totalEnergy > 0 ? ((highFreqEnergy / totalEnergy) * 100).toFixed(1) : '0.0'}%</span></li>
                </ul>
            </div>
        </div>
        <div class="mt-3">
            <h6>Анализ:</h6>
            <p id="energyAnalysis">
                ${getEnergyAnalysisText(energyAnalysis.energy_in_top_1_percent)}
            </p>
        </div>
    `;

        // Создаем график
        createEnergyChartFromData(dctCoeffs, totalEnergy, width, height);
    }

    function createEnergyChartFromData(dctCoeffs, totalEnergy, width, height) {
        try {
            const ctx = document.getElementById('energyChart')?.getContext('2d');
            if (!ctx) return;

            // Получаем все энергии коэффициентов
            const energies = [];
            for (let y = 0; y < height; y++) {
                for (let x = 0; x < width; x++) {
                    const coeff = dctCoeffs[y][x];
                    energies.push(coeff * coeff);
                }
            }

            // Сортируем по убыванию
            energies.sort((a, b) => b - a);

            // Создаем данные для кумулятивного графика
            const cumulativeData = [];
            let cumulative = 0;
            const step = Math.max(1, Math.floor(energies.length / 50)); // 50 точек на графике

            for (let i = 0; i < energies.length; i += step) {
                cumulative += energies[i];
                cumulativeData.push({
                    x: (i / energies.length) * 100,
                    y: (cumulative / totalEnergy) * 100
                });
            }

            // Добавляем последнюю точку
            if (energies.length > 0) {
                cumulativeData.push({
                    x: 100,
                    y: 100
                });
            }

            // Уничтожаем старый график, если есть
            if (window.energyChartInstance) {
                window.energyChartInstance.destroy();
            }

            // Создаем новый график
            window.energyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Накопленная энергия (%)',
                        data: cumulativeData,
                        borderColor: '#57568c',
                        backgroundColor: 'rgba(87, 86, 140, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Энергия: ${context.parsed.y.toFixed(1)}%`;
                                },
                                afterLabel: function(context) {
                                    return `${context.parsed.x.toFixed(1)}% коэффициентов`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Накопленная энергия (%)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Коэффициенты (отсортированы по убыванию энергии)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Ошибка создания графика энергии:', error);
        }
    }
    // Вспомогательная функция для текста анализа
    function getEnergyAnalysisText(energyPercent) {
        if (energyPercent > 90) {
            return 'Изображение имеет очень гладкую текстуру, почти вся энергия сосредоточена в низкочастотных коэффициентах. Хорошо поддается сжатию.';
        } else if (energyPercent > 70) {
            return 'Изображение имеет умеренную текстуру, большая часть энергии в низких частотах. Хорошо поддается сжатию.';
        } else if (energyPercent > 50) {
            return 'Изображение имеет заметную текстуру, энергия распределена более равномерно. Сжатие может привести к потере деталей.';
        } else {
            return 'Изображение имеет сложную текстуру или шум, энергия распределена по всему спектру. Сжатие может значительно ухудшить качество.';
        }
    }

    // Альтернативная функция для простого DCT
    async function trySimpleDCT() {
        if (!uploadedPixels) return;

        try {
            const width = uploadedPixels[0].length;
            const height = uploadedPixels.length;

            // Простое локальное применение DCT для демонстрации
            const smallWidth = Math.min(128, width);
            const smallHeight = Math.min(128, height);

            const smallPixels = [];
            for (let y = 0; y < smallHeight; y++) {
                const row = [];
                for (let x = 0; x < smallWidth; x++) {
                    row.push(uploadedPixels[Math.floor(y * height / smallHeight)][Math.floor(x * width / smallWidth)]);
                }
                smallPixels.push(row);
            }

            // Простой 2D DCT (упрощенная реализация)
            const dctResult = applySimpleLocalDCT(smallPixels, smallWidth, smallHeight);
            await createDctVisualization(dctResult, smallWidth, smallHeight);

        } catch (error) {
            console.error('Ошибка упрощенного DCT:', error);
        }
    }

    // Упрощенная локальная реализация DCT для демонстрации
    function applySimpleLocalDCT(pixels, width, height) {
        const dct = [];

        // Инициализируем массив
        for (let y = 0; y < height; y++) {
            dct[y] = new Array(width).fill(0);
        }

        // Простой 2D DCT (для демонстрации)
        for (let u = 0; u < height; u++) {
            for (let v = 0; v < width; v++) {
                let sum = 0;
                for (let x = 0; x < width; x++) {
                    for (let y = 0; y < height; y++) {
                        sum += pixels[y][x] *
                            Math.cos(Math.PI * u * (2 * x + 1) / (2 * width)) *
                            Math.cos(Math.PI * v * (2 * y + 1) / (2 * height));
                    }
                }
                const alphaU = (u === 0) ? 1 / Math.sqrt(2) : 1;
                const alphaV = (v === 0) ? 1 / Math.sqrt(2) : 1;
                dct[u][v] = alphaU * alphaV * sum / Math.sqrt(width * height);
            }
        }

        return dct;
    }

    // Простой fallback результат
    function showSimpleResults(width, height) {
        const task3Results = document.getElementById('task3Results');
        const uploadedImage = document.getElementById('uploadedImage');

        task3Results.innerHTML = `
        <h3>Результаты 2D DCT</h3>
        <div class="row">
            <div class="col-md-6">
                <h5>Исходное изображение</h5>
                <img src="${uploadedImage.src}" class="image-preview" alt="Оригинал" style="max-width: 100%; max-height: 300px;">
                <p class="text-center mt-2"><small>${width} × ${height} пикселей</small></p>
            </div>
            <div class="col-md-6">
                <h5>DCT выполнено успешно</h5>
                <div class="alert alert-success">
                    <p>2D DCT было успешно применено к изображению.</p>
                    <p>Для визуализации спектра требуется более мощный сервер или меньшее изображение.</p>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary" onclick="trySimpleDCT()">Показать упрощенный спектр</button>
        </div>
    `;
    }

    // Функция для создания спектра DCT локально
    async function createDctSpectrumLocal(dctCoeffs, width, height) {
        return new Promise((resolve, reject) => {
            try {
                // Создаем canvas для визуализации спектра
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');

                // Находим максимальное абсолютное значение для нормализации
                let maxVal = 0;
                for (let y = 0; y < height; y++) {
                    for (let x = 0; x < width; x++) {
                        const absVal = Math.abs(dctCoeffs[y][x]);
                        if (absVal > maxVal) {
                            maxVal = absVal;
                        }
                    }
                }

                if (maxVal === 0) {
                    // Если все коэффициенты нулевые, создаем черное изображение
                    ctx.fillStyle = 'black';
                    ctx.fillRect(0, 0, width, height);
                    resolve(canvas.toDataURL('image/png'));
                    return;
                }

                // Создаем ImageData для canvas
                const imageData = ctx.createImageData(width, height);

                // Заполняем пиксели на основе DCT коэффициентов (логарифмическая шкала)
                for (let y = 0; y < height; y++) {
                    for (let x = 0; x < width; x++) {
                        const val = Math.abs(dctCoeffs[y][x]);
                        // Логарифмическая шкала для лучшей видимости
                        const logVal = Math.log(1 + val);
                        const logMax = Math.log(1 + maxVal);
                        const normalized = logVal / logMax;
                        const intensity = Math.floor(normalized * 255);

                        const index = (y * width + x) * 4;
                        imageData.data[index] = intensity;     // R
                        imageData.data[index + 1] = intensity; // G
                        imageData.data[index + 2] = intensity; // B
                        imageData.data[index + 3] = 255;       // A
                    }
                }

                // Помещаем ImageData на canvas
                ctx.putImageData(imageData, 0, 0);

                // Конвертируем canvas в Data URL
                resolve(canvas.toDataURL('image/png'));

            } catch (error) {
                reject(error);
            }
        });
    }

    function analyzeEnergyDistribution(dctCoeffs, width, height) {
        try {
            // Даем время на обновление DOM
            setTimeout(() => {
                const energyPercentEl = document.getElementById('energyPercent');
                const energyAnalysisEl = document.getElementById('energyAnalysis');

                if (!energyPercentEl || !energyAnalysisEl) {
                    console.warn('Элементы для анализа энергии не найдены');
                    return;
                }

                // Вычисляем энергию каждого коэффициента
                const energies = [];
                let totalEnergy = 0;
                let dcValue = 0;
                let maxCoeff = 0;

                for (let y = 0; y < height; y++) {
                    for (let x = 0; x < width; x++) {
                        const coeff = dctCoeffs[y][x];
                        const energy = coeff * coeff;
                        energies.push({ x, y, energy, coeff });
                        totalEnergy += energy;

                        // DC коэффициент (0,0) - средняя яркость
                        if (x === 0 && y === 0) {
                            dcValue = coeff;
                        }

                        // Максимальный коэффициент (исключая DC)
                        if ((x !== 0 || y !== 0) && Math.abs(coeff) > Math.abs(maxCoeff)) {
                            maxCoeff = coeff;
                        }
                    }
                }

                // Сортируем коэффициенты по убыванию энергии
                energies.sort((a, b) => b.energy - a.energy);

                // Вычисляем энергию в первых 1% коэффициентов
                const top1Percent = Math.ceil(energies.length * 0.01);
                let top1Energy = 0;
                for (let i = 0; i < top1Percent; i++) {
                    top1Energy += energies[i].energy;
                }

                // Вычисляем распределение по квадрантам
                let lowFreqEnergy = 0;    // Верхний левый квадрант (1/4 изображения)
                let midFreqEnergy = 0;    // Центральная область
                let highFreqEnergy = 0;   // Нижний правый квадрант

                const lowFreqLimit = Math.floor(width / 4);

                for (let y = 0; y < height; y++) {
                    for (let x = 0; x < width; x++) {
                        const coeff = dctCoeffs[y][x];
                        const energy = coeff * coeff;

                        if (x < lowFreqLimit && y < lowFreqLimit) {
                            lowFreqEnergy += energy;
                        } else if (x >= width * 3/4 && y >= height * 3/4) {
                            highFreqEnergy += energy;
                        } else {
                            midFreqEnergy += energy;
                        }
                    }
                }

                // Обновляем процент энергии
                const energyPercent = totalEnergy > 0 ? ((top1Energy / totalEnergy) * 100) : 0;
                energyPercentEl.textContent = energyPercent.toFixed(1) + '%';

                // Обновляем анализ
                let analysis = '';
                if (energyPercent > 90) {
                    analysis = 'Изображение имеет очень гладкую текстуру, почти вся энергия сосредоточена в низкочастотных коэффициентах. Хорошо поддается сжатию.';
                } else if (energyPercent > 70) {
                    analysis = 'Изображение имеет умеренную текстуру, большая часть энергии в низких частотах. Хорошо поддается сжатию.';
                } else if (energyPercent > 50) {
                    analysis = 'Изображение имеет заметную текстуру, энергия распределена более равномерно. Сжатие может привести к потере деталей.';
                } else {
                    analysis = 'Изображение имеет сложную текстуру или шум, энергия распределена по всему спектру. Сжатие может значительно ухудшить качество.';
                }

                energyAnalysisEl.textContent = analysis;

                // Обновляем другие элементы, если они существуют
                updateEnergyUIElements({
                    totalEnergy,
                    dcValue,
                    maxCoeff,
                    width,
                    height,
                    lowFreqEnergy,
                    midFreqEnergy,
                    highFreqEnergy
                });

                // Создаем график
                createEnergyChart(energies, totalEnergy);

            }, 300); // Даем больше времени для рендеринга DOM

        } catch (error) {
            console.error('Ошибка анализа энергии:', error);
        }
    }
    function updateEnergyUIElements(data) {
        const elements = {
            'imageSize': `${data.width} × ${data.height}`,
            'coefficientsCount': data.width * data.height,
            'dcValue': data.dcValue.toFixed(2),
            'maxCoefficient': data.maxCoeff.toFixed(2),
            'lowFreqEnergy': data.totalEnergy > 0 ? ((data.lowFreqEnergy / data.totalEnergy) * 100).toFixed(1) + '%' : '0.0%',
            'midFreqEnergy': data.totalEnergy > 0 ? ((data.midFreqEnergy / data.totalEnergy) * 100).toFixed(1) + '%' : '0.0%',
            'highFreqEnergy': data.totalEnergy > 0 ? ((data.highFreqEnergy / data.totalEnergy) * 100).toFixed(1) + '%' : '0.0%'
        };

        for (const [id, value] of Object.entries(elements)) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
    }

    function createEnergyChart(energies, totalEnergy) {
        try {
            const analysisContent = document.getElementById('energyAnalysisContent');
            if (!analysisContent) return;

            // Проверяем, есть ли уже график, если нет - создаем контейнер
            if (!document.getElementById('energyChart')) {
                const chartContainer = document.createElement('div');
                chartContainer.style.position = 'relative';
                chartContainer.style.height = '200px';
                chartContainer.style.width = '100%';
                chartContainer.innerHTML = '<canvas id="energyChart"></canvas>';

                const currentAnalysisContent = analysisContent.querySelector('#energyChartContainer') || analysisContent;
                if (currentAnalysisContent.querySelector('#energyChart')) {
                    return; // График уже существует
                }

                currentAnalysisContent.appendChild(chartContainer);
            }

            const ctx = document.getElementById('energyChart').getContext('2d');

            // Подготавливаем данные для графика
            const cumulativeEnergies = [];
            let cumulative = 0;
            const step = Math.max(1, Math.floor(energies.length / 100));

            for (let i = 0; i < energies.length; i += step) {
                cumulative += energies[i].energy;
                cumulativeEnergies.push({
                    x: (i / energies.length) * 100,
                    y: (cumulative / totalEnergy) * 100
                });
            }

            // Добавляем последнюю точку
            if (energies.length > 0) {
                cumulativeEnergies.push({
                    x: 100,
                    y: 100
                });
            }

            // Уничтожаем старый график, если есть
            if (window.energyChartInstance) {
                window.energyChartInstance.destroy();
            }

            // Создаем новый график
            window.energyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Накопленная энергия',
                        data: cumulativeEnergies,
                        borderColor: '#57568c',
                        backgroundColor: 'rgba(87, 86, 140, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Энергия: ${context.parsed.y.toFixed(1)}%`;
                                },
                                afterLabel: function(context) {
                                    return `${context.parsed.x.toFixed(1)}% коэффициентов`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Накопленная энергия (%)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Коэффициенты (отсортированы по убыванию энергии)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Ошибка создания графика:', error);
        }
    }

    // Задание 4: Блочное DCT 8x8 (JPEG)
    async function applyBlockDCT() {
        const strength = document.getElementById('quantizationStrength').value;

        // Получаем пиксели из загруженного изображения
        const imageContainer = document.getElementById('imageContainer');
        const img = imageContainer.querySelector('img');

        if (!img) {
            alert('Сначала загрузите изображение в Задании 3');
            return;
        }

        try {
            // Показываем индикатор загрузки
            const task4Results = document.getElementById('task4Results');
            task4Results.style.display = 'block';
            task4Results.innerHTML = '<div class="alert alert-info">Обработка изображения...</div>';

            // Создаем canvas для получения пикселей
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Ждем загрузки изображения
            await new Promise((resolve, reject) => {
                if (img.complete) {
                    resolve();
                } else {
                    img.onload = resolve;
                    img.onerror = reject;
                }
            });

            // Для больших изображений ограничиваем размер для производительности
            const maxSize = 512;
            let scale = 1;

            if (img.naturalWidth > maxSize || img.naturalHeight > maxSize) {
                scale = Math.min(maxSize / img.naturalWidth, maxSize / img.naturalHeight);
            }

            canvas.width = Math.floor(img.naturalWidth * scale);
            canvas.height = Math.floor(img.naturalHeight * scale);

            // Рисуем изображение на canvas с масштабированием
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            // Получаем данные пикселей
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const pixels = [];

            // Конвертируем в градации серого и создаем массив пикселей
            for (let y = 0; y < canvas.height; y++) {
                const row = [];
                for (let x = 0; x < canvas.width; x++) {
                    const index = (y * canvas.width + x) * 4;
                    const r = imageData.data[index];
                    const g = imageData.data[index + 1];
                    const b = imageData.data[index + 2];
                    // Преобразуем в оттенки серого (среднее значение)
                    const gray = Math.round((r + g + b) / 3);
                    row.push(gray);
                }
                pixels.push(row);
            }

            console.log(`Отправляем изображение: ${canvas.width}x${canvas.height}, сила квантования: ${strength}`);

            // Отправляем запрос на сервер
            const res = await fetch('/lab6/dct8x8-jpeg', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    pixels: pixels,
                    strength: parseInt(strength),
                    width: canvas.width,
                    height: canvas.height
                })
            });

            // Проверяем статус ответа
            if (!res.ok) {
                const text = await res.text();
                throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
            }

            const data = await res.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Отображаем результаты
            task4Results.innerHTML = `
                <h3>Результаты JPEG-сжатия</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Оригинальное изображение</h5>
                        <img src="${img.src}" id="originalImage" class="image-preview" alt="Оригинал" style="max-width: 100%; max-height: 300px;">
                        <p class="text-muted">${canvas.width} × ${canvas.height} пикселей</p>
                    </div>
                    <div class="col-md-6">
                        <h5>После сжатия</h5>
                        <img src="${data.compressed_path}" id="compressedImage" class="image-preview" alt="После сжатия" style="max-width: 100%; max-height: 300px;">
                        <p class="text-muted">Качество: ${data.psnr} дБ PSNR</p>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Метрики качества</h5>
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>Метрика</th>
                                <th>Значение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>PSNR (дБ)</td>
                                <td id="psnrValue">${data.psnr}</td>
                            </tr>
                            <tr>
                                <td>Размер изображения</td>
                                <td>${canvas.width} × ${canvas.height} пикселей</td>
                            </tr>
                            <tr>
                                <td>Количество блоков</td>
                                <td>${data.blocks_count}</td>
                            </tr>
                            <tr>
                                <td>Сила квантования</td>
                                <td>${data.strength}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <h5>Анализ качества</h5>
                    <div id="artifactsInfo">
                        <ul>
                            ${data.psnr > 40 ?
                '<li><span class="text-success">Отличное качество</span> - артефакты практически не заметны</li>' :
                data.psnr > 30 ?
                    '<li><span class="text-warning">Хорошее качество</span> - легкая блочность при детальном рассмотрении</li>' :
                    '<li><span class="text-danger">Среднее качество</span> - заметная блочность и потеря деталей</li>'
            }
                            <li>PSNR > 40 дБ: отличное качество</li>
                            <li>PSNR 30-40 дБ: хорошее качество</li>
                            <li>PSNR < 30 дБ: среднее/низкое качество</li>
                        </ul>
                    </div>
                </div>
            `;

        } catch (error) {
            console.error('Ошибка при применении блочного DCT:', error);
            document.getElementById('task4Results').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Ошибка</h5>
                    <p>${error.message}</p>
                    <p>Попробуйте уменьшить размер изображения или выбрать меньшую силу квантования.</p>
                </div>
            `;
        }
    }

    // Вспомогательные функции
    function clearSignal() {
        originalSignal = [];
        dctCoeffs = [];
        uploadedPixels = null;
        dctCoefficients = null;

        document.getElementById('signalInfo').style.display = 'none';
        document.getElementById('task1Results').style.display = 'none';
        document.getElementById('task2Results').style.display = 'none';
        document.getElementById('task3Results').style.display = 'none';
        document.getElementById('task4Results').style.display = 'none';

        if (signalChart) signalChart.destroy();
        if (zeroChart) zeroChart.destroy();

        // Очищаем canvas
        let ctx1 = document.getElementById('signalChart').getContext('2d');
        ctx1.clearRect(0, 0, document.getElementById('signalChart').width, document.getElementById('signalChart').height);

        let ctx2 = document.getElementById('zeroChart').getContext('2d');
        ctx2.clearRect(0, 0, document.getElementById('zeroChart').width, document.getElementById('zeroChart').height);

        // Очищаем контейнеры изображений
        document.getElementById('imageContainer').innerHTML = '';
    }

    function plotSignal(canvasId, data, label) {
        const ctx = document.getElementById(canvasId).getContext('2d');

        // Если график уже существует, уничтожаем его
        if (canvasId === 'signalChart' && signalChart) {
            signalChart.destroy();
        } else if (canvasId === 'zeroChart' && zeroChart) {
            zeroChart.destroy();
        }

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map((_, i) => i),
                datasets: [{
                    label,
                    data,
                    borderColor: canvasId === 'signalChart' ? '#57568c' : '#28a745',
                    backgroundColor: canvasId === 'signalChart' ? 'rgba(87, 86, 140, 0.1)' : 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Сохраняем ссылку на график
        if (canvasId === 'signalChart') {
            signalChart = chart;
        } else {
            zeroChart = chart;
        }
    }

    function calculateReconstructionError(coeffs) {
        // Простой расчет ошибки восстановления
        let error = 0;
        for (let i = 0; i < coeffs.length; i++) {
            error += Math.pow(coeffs[i], 2);
        }
        return (error / coeffs.length).toFixed(6);
    }
</script>
</body>
</html>
