<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обработка изображений и гистограммы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .histogram-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .operation-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .slider-container {
            margin: 15px 0;
        }
        .image-comparison {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .image-comparison-item {
            flex: 1;
            min-width: 300px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0">Обработка изображений и анализ гистограмм</h3>
                </div>
                <div class="card-body">

                    <!-- Сообщения об успехе/ошибке -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Форма загрузки -->
                    <form action="{{ route('image.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <div class="upload-area mb-3" id="uploadArea">
                            <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp" hidden>
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Перетащите изображение или нажмите для выбора</h5>
                                <p class="text-muted">
                                    Поддерживаемые форматы: JPG, JPEG, PNG, GIF, BMP, WEBP<br>
                                    Максимальный размер: 5MB
                                </p>
                            </div>
                        </div>

                        <!-- Предпросмотр -->
                        <div id="previewContainer" class="text-center mb-3" style="display: none;">
                            <img id="imagePreview" class="preview-image mb-2">
                            <div class="mt-2">
                                <button type="button" id="removePreview" class="btn btn-outline-danger btn-sm">
                                    Удалить предпросмотр
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-upload me-2"></i>Загрузить изображение
                            </button>
                        </div>
                    </form>

                    <!-- Отображение загруженного изображения и гистограммы -->
                    @if(session('image_url'))
                        <div class="mt-5">
                            <h4 class="text-center mb-4">Исходное изображение и гистограмма</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-center">
                                        <img src="{{ session('image_url') }}" class="preview-image" alt="Загруженное изображение" id="originalImage">
                                        <div class="mt-3">
                                            <h6>Информация о файле:</h6>
                                            @php $info = session('file_info'); @endphp
                                            <p><strong>Имя:</strong> {{ $info['original_name'] ?? '' }}</p>
                                            <p><strong>Размер:</strong> {{ number_format(($info['file_size'] ?? 0) / 1024, 2) }} KB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="histogram-container">
                                        <h6 class="text-center">Гистограмма исходного изображения</h6>
                                        <canvas id="originalHistogram" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Скрытые данные для JavaScript -->
                            <input type="hidden" id="originalImagePath" value="{{ $info['file_path'] ?? '' }}">
                            <input type="hidden" id="originalHistogramUrl" value="{{ session('histogram_url') }}">

                            <!-- Кнопка удаления -->
                            <form action="{{ route('image.delete') }}" method="POST" class="text-center mt-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="file_path" value="{{ $info['file_path'] ?? '' }}">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Удалить изображение?')">
                                    <i class="fas fa-trash me-2"></i>Удалить изображение
                                </button>
                            </form>
                        </div>

                        <!-- Операции обработки изображений -->
                        <div class="mt-5">
                            <h4 class="text-center mb-4">Операции обработки изображений</h4>

                            <!-- 1. Просветление изображения -->
                            <div class="operation-section" id="brightnessSection">
                                <h5>1. Просветление изображения</h5>
                                <div class="slider-container">
                                    <label for="brightnessSlider" class="form-label">Уровень просветления: <span id="brightnessValue">0</span></label>
                                    <input type="range" class="form-range" id="brightnessSlider" min="-100" max="100" value="0">
                                </div>
                                <button class="btn btn-outline-primary" onclick="applyOperation('brightness')">
                                    Применить просветление
                                </button>
                                <div id="brightnessResult" class="mt-3"></div>
                            </div>

                            <!-- 2. Инвертирование изображения -->
                            <div class="operation-section" id="inversionSection">
                                <h5>2. Инвертирование изображения</h5>
                                <div class="mb-3">
                                    <label class="form-label">Тип инвертирования:</label>
                                    <div>
                                        <input type="radio" class="btn-check" name="inversionType" id="fullInversion" value="full" checked>
                                        <label class="btn btn-outline-secondary" for="fullInversion">Полное</label>

                                        <input type="radio" class="btn-check" name="inversionType" id="partialRedInversion" value="partial_red">
                                        <label class="btn btn-outline-secondary" for="partialRedInversion">Частичное (красный)</label>

                                        <input type="radio" class="btn-check" name="inversionType" id="partialGreenInversion" value="partial_green">
                                        <label class="btn btn-outline-secondary" for="partialGreenInversion">Частичное (зеленый)</label>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary" onclick="applyOperation('inversion')">
                                    Применить инвертирование
                                </button>
                                <div id="inversionResult" class="mt-3"></div>
                            </div>

                            <!-- 3. Пороговое преобразование -->
                            <div class="operation-section" id="thresholdSection">
                                <h5>3. Пороговое преобразование</h5>
                                <div class="mb-3">
                                    <label class="form-label">Тип преобразования:</label>
                                    <div>
                                        <input type="radio" class="btn-check" name="thresholdType" id="binaryThreshold" value="binary" checked>
                                        <label class="btn btn-outline-secondary" for="binaryThreshold">Бинарное</label>

                                        <input type="radio" class="btn-check" name="thresholdType" id="sliceThreshold" value="slice">
                                        <label class="btn btn-outline-secondary" for="sliceThreshold">Яркостные срезы</label>
                                    </div>
                                </div>
                                <div class="slider-container">
                                    <label for="thresholdSlider" class="form-label">Порог: <span id="thresholdValue">128</span></label>
                                    <input type="range" class="form-range" id="thresholdSlider" min="0" max="255" value="128">
                                </div>
                                <button class="btn btn-outline-primary" onclick="applyOperation('threshold')">
                                    Применить пороговое преобразование
                                </button>
                                <div id="thresholdResult" class="mt-3"></div>
                            </div>

                            <!-- 4. Изменение контраста -->
                            <div class="operation-section" id="contrastSection">
                                <h5>4. Изменение контраста</h5>
                                <div class="mb-3">
                                    <label class="form-label">Уровень контраста:</label>
                                    <div>
                                        <input type="radio" class="btn-check" name="contrastType" id="lowContrast" value="low">
                                        <label class="btn btn-outline-secondary" for="lowContrast">Низкий</label>

                                        <input type="radio" class="btn-check" name="contrastType" id="mediumContrast" value="medium" checked>
                                        <label class="btn btn-outline-secondary" for="mediumContrast">Средний</label>

                                        <input type="radio" class="btn-check" name="contrastType" id="highContrast" value="high">
                                        <label class="btn btn-outline-secondary" for="highContrast">Высокий</label>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary" onclick="applyOperation('contrast')">
                                    Применить изменение контраста
                                </button>
                                <div id="contrastResult" class="mt-3"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('imageInput');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const removePreview = document.getElementById('removePreview');
        const submitBtn = document.getElementById('submitBtn');

        // Обработчик клика по области загрузки
        if (uploadArea) {
            uploadArea.addEventListener('click', () => {
                imageInput.click();
            });
        }

        // Обработчик изменения файла
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    handleFileSelection(this.files[0]);
                }
            });
        }

        // Drag and Drop функциональность
        if (uploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.remove('dragover');
                }, false);
            });

            uploadArea.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });
        }

        // Обработка выбранного файла
        function handleFileSelection(file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Пожалуйста, выберите файл изображения (JPG, PNG, GIF, BMP, WEBP)');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('Размер файла не должен превышать 5MB');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
                submitBtn.disabled = false;
            }
            reader.readAsDataURL(file);
        }

        // Удаление предпросмотра
        if (removePreview) {
            removePreview.addEventListener('click', function() {
                imageInput.value = '';
                previewContainer.style.display = 'none';
                submitBtn.disabled = true;
            });
        }

        // Обновление значений слайдеров
        const brightnessSlider = document.getElementById('brightnessSlider');
        const brightnessValue = document.getElementById('brightnessValue');
        if (brightnessSlider) {
            brightnessSlider.addEventListener('input', function() {
                brightnessValue.textContent = this.value;
            });
        }

        const thresholdSlider = document.getElementById('thresholdSlider');
        const thresholdValue = document.getElementById('thresholdValue');
        if (thresholdSlider) {
            thresholdSlider.addEventListener('input', function() {
                thresholdValue.textContent = this.value;
            });
        }

        // Загрузка и отображение гистограммы исходного изображения
        const originalHistogramUrl = document.getElementById('originalHistogramUrl');
        if (originalHistogramUrl) {
            loadAndDisplayHistogram(originalHistogramUrl.value, 'originalHistogram', 'Исходное изображение');
        }
    });

    // Функция применения операции
    async function applyOperation(operation) {
        const imagePath = document.getElementById('originalImagePath').value;
        let parameters = {};

        switch (operation) {
            case 'brightness':
                parameters.value = parseInt(document.getElementById('brightnessSlider').value);
                break;
            case 'inversion':
                parameters.type = document.querySelector('input[name="inversionType"]:checked').value;
                break;
            case 'threshold':
                parameters.type = document.querySelector('input[name="thresholdType"]:checked').value;
                parameters.value = parseInt(document.getElementById('thresholdSlider').value);
                break;
            case 'contrast':
                parameters.type = document.querySelector('input[name="contrastType"]:checked').value;
                break;
        }

        try {
            console.log('Отправка запроса на обработку:', { operation, parameters });

            const response = await fetch('{{ route("image.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    image_path: imagePath,
                    operation: operation,
                    parameters: parameters
                })
            });

            const result = await response.json();
            console.log('Ответ сервера:', result);

            if (result.error) {
                alert('Ошибка: ' + result.error);
                return;
            }

            // Отображаем результат
            displayResult(operation, result);

        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при обработке изображения: ' + error.message);
        }
    }

    // Функция отображения результата
    function displayResult(operation, result) {
        const resultDiv = document.getElementById(operation + 'Result');

        resultDiv.innerHTML = `
            <div class="image-comparison">
                <div class="image-comparison-item">
                    <h6>Обработанное изображение</h6>
                    <img src="${result.processed_url}" class="preview-image">
                </div>
                <div class="image-comparison-item">
                    <h6>Гистограмма обработанного изображения</h6>
                    <div class="histogram-container">
                        <canvas id="${operation}Histogram" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        `;

        // Загружаем и отображаем гистограмму обработанного изображения
        loadAndDisplayHistogram(result.histogram_url, operation + 'Histogram', 'Обработанное изображение');
    }

    // Функция загрузки и отображения гистограммы
    async function loadAndDisplayHistogram(histogramUrl, canvasId, label) {
        try {
            const response = await fetch('{{ route("image.histogram.data") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    histogram_url: histogramUrl
                })
            });

            const histogramData = await response.json();

            if (histogramData.error) {
                console.error('Ошибка загрузки гистограммы:', histogramData.error);
                return;
            }

            const ctx = document.getElementById(canvasId).getContext('2d');

            // Создаем данные для Chart.js
            const labels = Array.from({length: 256}, (_, i) => i);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Красный канал',
                            data: histogramData.red,
                            borderColor: 'rgba(255, 0, 0, 0.8)',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: 'Зеленый канал',
                            data: histogramData.green,
                            borderColor: 'rgba(0, 255, 0, 0.8)',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: 'Синий канал',
                            data: histogramData.blue,
                            borderColor: 'rgba(0, 0, 255, 0.8)',
                            backgroundColor: 'rgba(0, 0, 255, 0.1)',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: 'Яркость',
                            data: histogramData.gray,
                            borderColor: 'rgba(128, 128, 128, 0.8)',
                            backgroundColor: 'rgba(128, 128, 128, 0.1)',
                            borderWidth: 1,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: label
                        },
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Уровень яркости'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Частота (%)'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Ошибка загрузки гистограммы:', error);
        }
    }
</script>
</body>
</html>
