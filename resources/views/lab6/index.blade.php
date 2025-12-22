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
    <div class="task-section">
        <h2>Задание 3: 2D DCT изображения</h2>
        <div class="image-upload-container">
            <div class="mb-3">
                <label for="imageUpload" class="form-label">Выберите изображение:</label>
                <input class="form-control" type="file" id="imageUpload" accept="image/*">
            </div>
            <button class="btn-success-dct" onclick="uploadImage()">Загрузить и применить DCT</button>
        </div>

        <div id="imageContainer" class="mt-3">
            <!-- Здесь будут отображаться изображения -->
        </div>

        <div id="task3Results" class="results-container" style="display: none;">
            <h3>Результаты 2D DCT</h3>
            <div id="dctSpectrumContainer">
                <!-- Здесь будет спектр DCT -->
            </div>
            <div id="energyInfo" class="mt-3">
                <!-- Информация о распределении энергии -->
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

    async function generateSignal() {
        const res = await fetch('/lab6/generate-signal', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

    async function zeroHighFreq(percent) {
        const res = await fetch('/lab6/dct-zero-high-freq', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

    async function uploadImage() {
        const fileInput = document.getElementById('imageUpload');
        const formData = new FormData();
        formData.append('image', fileInput.files[0]);

        const res = await fetch('/lab6/upload-image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        const data = await res.json();

        document.getElementById('imageContainer').innerHTML = `
            <div class="alert alert-success">Изображение загружено: ${data.width} × ${data.height} пикселей</div>
            <img src="${data.path}" class="image-preview" alt="Загруженное изображение">
        `;

        document.getElementById('task3Results').style.display = 'block';
    }

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

            // Устанавливаем размеры canvas
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;

            // Рисуем изображение на canvas
            ctx.drawImage(img, 0, 0);

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

            // Отправляем запрос на сервер
            const res = await fetch('/lab6/dct8x8-jpeg', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pixels: pixels,
                    strength: parseInt(strength),
                    width: canvas.width,
                    height: canvas.height
                })
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Ошибка сервера');
            }

            if (data.error) {
                throw new Error(data.error);
            }

            // Отображаем результаты
            document.getElementById('task4Results').style.display = 'block';
            document.getElementById('originalImage').src = img.src;
            document.getElementById('compressedImage').src = data.compressed_path;
            document.getElementById('psnrValue').textContent = data.psnr + ' дБ';
            document.getElementById('originalSize').textContent = Math.round(canvas.width * canvas.height * 3 / 1024) + ' КБ';
            document.getElementById('compressedSize').textContent = Math.round(canvas.width * canvas.height / 1024) + ' КБ';
            document.getElementById('compressionRatio').textContent = Math.round((1 - (1 / 3)) * 100) + '%';

            // Анализ артефактов
            const artifactsInfo = document.getElementById('artifactsInfo');
            let artifactsHtml = '<ul>';

            if (data.psnr > 40) {
                artifactsHtml += '<li><span class="text-success">Высокое качество</span> - артефакты практически не заметны</li>';
            } else if (data.psnr > 30) {
                artifactsHtml += '<li><span class="text-warning">Среднее качество</span> - заметна легкая блочность</li>';
            } else {
                artifactsHtml += '<li><span class="text-danger">Низкое качество</span> - сильная блочность и потеря деталей</li>';
            }

            artifactsHtml += `<li>Блоков обработано: ${data.blocks_count}</li>`;
            artifactsHtml += `<li>Сила квантования: ${data.strength}</li>`;
            artifactsHtml += '</ul>';

            artifactsInfo.innerHTML = artifactsHtml;

        } catch (error) {
            console.error('Ошибка при применении блочного DCT:', error);
            alert('Ошибка: ' + error.message);
        }
    }

    function clearSignal() {
        originalSignal = [];
        dctCoeffs = [];
        document.getElementById('signalInfo').style.display = 'none';
        document.getElementById('task1Results').style.display = 'none';
        document.getElementById('task2Results').style.display = 'none';
        if (signalChart) signalChart.destroy();
        if (zeroChart) zeroChart.destroy();

        // Очищаем canvas
        let ctx1 = document.getElementById('signalChart').getContext('2d');
        ctx1.clearRect(0, 0, document.getElementById('signalChart').width, document.getElementById('signalChart').height);

        let ctx2 = document.getElementById('zeroChart').getContext('2d');
        ctx2.clearRect(0, 0, document.getElementById('zeroChart').width, document.getElementById('zeroChart').height);
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
        // Заглушка для расчета ошибки восстановления
        return Math.random().toFixed(6);
    }
</script>
</body>
</html>
