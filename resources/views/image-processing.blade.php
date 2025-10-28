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
            height: 250px; /* Увеличиваем высоту */
            position: relative;
            overflow: hidden;
        }

        .histogram-container canvas {
            width: 100% !important;
            height: calc(100% - 40px) !important; /* Учитываем место для заголовка */
            display: block;
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
                                        <h6 class="text-center mb-2">Гистограмма исходного изображения</h6>
                                        <p class="text-center text-muted small">Нажмите для увеличения</p>
                                        <canvas id="originalHistogram" width="400" height="100"></canvas>
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

                                <!-- Убираем радиокнопки и добавляем ползунок -->
                                <div class="slider-container">
                                    <label for="contrastSlider" class="form-label">Уровень контраста: <span id="contrastValue">0</span></label>
                                    <input type="range" class="form-range" id="contrastSlider" min="-100" max="100" value="0">
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>-100</span>
                                        <span>Низкий</span>
                                        <span>0</span>
                                        <span>Высокий</span>
                                        <span>+100</span>
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
<!-- Модальное окно для увеличенной гистограммы -->
<div class="modal fade histogram-modal" id="histogramModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="histogramModalTitle">Детальная гистограмма</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <canvas id="enlargedHistogram"></canvas>
                <div class="histogram-tooltip" id="histogramTooltip"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentHistogramData = null;
    let currentHistogramLabel = '';

    async function loadAndDisplayHistogram(histogramUrl, canvasId, label, isCompact = true) {
        try {
            console.log('Загрузка гистограммы:', histogramUrl);

            if (!histogramUrl) {
                console.error('URL гистограммы не указан');
                return;
            }

            const response = await fetch('{{ route("image.histogram.data") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    histogram_url: histogramUrl
                })
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Не JSON ответ:', text);
                throw new Error('Сервер вернул не JSON ответ. Возможно, произошла ошибка на сервере.');
            }

            const histogramData = await response.json();
            console.log('Данные гистограммы получены:', histogramData);

            if (!response.ok) {
                throw new Error(histogramData.error || 'Произошла ошибка при загрузке гистограммы');
            }

            if (histogramData.error) {
                console.error('Ошибка в данных:', histogramData.error);
                return;
            }

            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                console.error('Canvas не найден:', canvasId);
                return;
            }

            const container = canvas.parentElement;

            // УНИЧТОЖАЕМ ПРЕДЫДУЩИЙ ГРАФИК
            if (canvas.chart) {
                canvas.chart.destroy();
                canvas.chart = null;
            }

            // ОЧИЩАЕМ CANVAS
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const containerStyle = window.getComputedStyle(container);
            const paddingLeft = parseInt(containerStyle.paddingLeft) || 0;
            const paddingRight = parseInt(containerStyle.paddingRight) || 0;
            const paddingTop = parseInt(containerStyle.paddingTop) || 0;
            const paddingBottom = parseInt(containerStyle.paddingBottom) || 0;

            const availableWidth = container.clientWidth - paddingLeft - paddingRight;
            const availableHeight = container.clientHeight - paddingTop - paddingBottom;

            // Устанавливаем размеры canvas
            canvas.width = availableWidth;
            canvas.height = availableHeight;
            canvas.style.width = availableWidth + 'px';
            canvas.style.height = availableHeight + 'px';

            console.log('Размеры контейнера:', availableWidth, 'x', availableHeight);

            // Сохраняем данные для возможности увеличения
            canvas.histogramData = histogramData;
            canvas.histogramLabel = label;

            // Добавляем обработчик клика для увеличения
            container.addEventListener('click', function() {
                showEnlargedHistogram(histogramData, label);
            });

            const compactOptions = {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 5,
                        right: 5,
                        top: 5,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false,
                        min: 0,
                        grid: {
                            display: false
                        },
                        ticks: {
                            display: false
                        }
                    },
                    y: {
                        display: false,
                        beginAtZero: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            display: false
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 0,
                        hoverRadius: 0
                    },
                    line: {
                        borderWidth: 1,
                        tension: 0.4
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 0
                }
            };

            // Настройки для полноразмерного отображения
            const fullOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: label,
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Уровень яркости'
                        },
                        min: 0,
                        max: 255
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Частота пикселей'
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value; // выводим реальные значения количества пикселей
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }

                    }

                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        radius: 0,
                        hoverRadius: 4
                    }
                }
            };

            const options = isCompact ? compactOptions : fullOptions;

            const labels = Array.from({length: 256}, (_, i) => i);

            canvas.chart = new Chart(ctx, {
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
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Зеленый канал',
                            data: histogramData.green,
                            borderColor: 'rgba(0, 255, 0, 0.8)',
                            backgroundColor: 'rgba(0, 255, 0, 0.1)',
                            borderWidth: 1,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Синий канал',
                            data: histogramData.blue,
                            borderColor: 'rgba(0, 0, 255, 0.8)',
                            backgroundColor: 'rgba(0, 0, 255, 0.1)',
                            borderWidth: 1,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Яркость',
                            data: histogramData.gray,
                            borderColor: 'rgba(128, 128, 128, 0.8)',
                            backgroundColor: 'rgba(128, 128, 128, 0.1)',
                            borderWidth: 1,
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: options
            });

            // ПЕРЕРИСОВЫВАЕМ ГРАФИК С УЧЕТОМ НОВЫХ РАЗМЕРОВ
            setTimeout(() => {
                if (canvas.chart) {
                    canvas.chart.resize();
                    canvas.chart.update('none'); // Обновляем без анимации
                }
            }, 50);

            console.log('Гистограмма отображена успешно. Размеры:', availableWidth, 'x', availableHeight);

        } catch (error) {
            console.error('Ошибка загрузки гистограммы:', error);
            const canvas = document.getElementById(canvasId);
            if (canvas) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#6c757d';
                ctx.textAlign = 'center';
                ctx.font = '12px Arial';
                ctx.fillText('Гистограмма недоступна', canvas.width / 2, canvas.height / 2);
            }
        }
    }

    function showEnlargedHistogram(histogramData, label) {
        currentHistogramData = histogramData;
        currentHistogramLabel = label;

        const modal = new bootstrap.Modal(document.getElementById('histogramModal'));
        const modalTitle = document.getElementById('histogramModalTitle');
        modalTitle.textContent = label;

        modal.show();

        // Даем время на отображение модального окна перед инициализацией графика
        setTimeout(() => {
            createEnlargedHistogram(histogramData, label);
        }, 100);
    }

    // Создание увеличенной гистограммы
    // Создание увеличенной гистограммы с правильными значениями на оси Y
    function createEnlargedHistogram(histogramData, label) {
        const canvas = document.getElementById('enlargedHistogram');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Уничтожаем предыдущий график
        if (canvas.chart) {
            canvas.chart.destroy();
        }

        const labels = Array.from({length: 256}, (_, i) => i);

        // Находим максимальное значение для масштабирования оси Y
        const maxRed = Math.max(...histogramData.red);
        const maxGreen = Math.max(...histogramData.green);
        const maxBlue = Math.max(...histogramData.blue);
        const maxGray = Math.max(...histogramData.gray);
        const maxValue = Math.max(maxRed, maxGreen, maxBlue, maxGray);

        // Форматируем большие числа для читаемости
        function formatNumber(value) {
            if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + 'M';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(1) + 'K';
            }
            return value.toString();
        }

        canvas.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Красный канал',
                        data: histogramData.red,
                        borderColor: 'rgba(255, 0, 0, 0.9)',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Зеленый канал',
                        data: histogramData.green,
                        borderColor: 'rgba(0, 255, 0, 0.9)',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Синий канал',
                        data: histogramData.blue,
                        borderColor: 'rgba(0, 0, 255, 0.9)',
                        backgroundColor: 'rgba(0, 0, 255, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Яркость',
                        data: histogramData.gray,
                        borderColor: 'rgba(128, 128, 128, 0.9)',
                        backgroundColor: 'rgba(128, 128, 128, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: label,
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                const brightness = context.dataIndex;
                                return `${label}: ${value.toLocaleString()} пикселей (яркость: ${brightness})`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Уровень яркости (0-255)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        min: 0,
                        max: 255,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Количество пикселей',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        beginAtZero: true,
                        suggestedMax: maxValue * 1.1, // 10% запас сверху
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value; // выводим реальные значения количества пикселей
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        radius: 0,
                        hoverRadius: 6,
                        hoverBorderWidth: 3
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Обновленная функция отображения результата операции
    function displayResult(operation, result) {
        const resultDiv = document.getElementById(operation + 'Result');

        resultDiv.innerHTML = `
        <div class="image-comparison">
            <div class="image-comparison-item">
                <h6>Обработанное изображение</h6>
                <img src="${result.processed_url}" class="preview-image"
                     onload="console.log('Изображение загружено: ${result.processed_url}')"
                     onerror="console.error('Ошибка загрузки изображения: ${result.processed_url}')">
            </div>
            <div class="image-comparison-item">
                <h6>Гистограмма обработанного изображения</h6>
                <div class="histogram-container">
                    <canvas id="${operation}Histogram" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
    `;

        // Загружаем и отображаем гистограмму обработанного изображения в компактном виде
        if (result.histogram_url) {
            console.log('Загрузка гистограммы для операции:', operation, 'URL:', result.histogram_url);
            setTimeout(() => {
                loadAndDisplayHistogram(result.histogram_url, operation + 'Histogram', 'Обработанное изображение', true);
            }, 100);
        } else {
            console.error('URL гистограммы не указан для операции:', operation);
        }
    }

    // Обновленная функция отображения результата
    // function displayResult(operation, result) {
    //     const resultDiv = document.getElementById(operation + 'Result');
    //
    //     resultDiv.innerHTML = `
    //     <div class="image-comparison">
    //         <div class="image-comparison-item">
    //             <h6>Обработанное изображение</h6>
    //             <img src="${result.processed_url}" class="preview-image" onload="console.log('Изображение загружено: ${result.processed_url}')" onerror="console.error('Ошибка загрузки изображения: ${result.processed_url}')">
    //         </div>
    //         <div class="image-comparison-item">
    //             <h6>Гистограмма обработанного изображения</h6>
    //             <div class="histogram-container">
    //                 <canvas id="${operation}Histogram" width="400" height="200"></canvas>
    //             </div>
    //         </div>
    //     </div>
    // `;
    //
    //     // Загружаем и отображаем гистограмму обработанного изображения
    //     if (result.histogram_url) {
    //         console.log('Загрузка гистограммы для операции:', operation, 'URL:', result.histogram_url);
    //         setTimeout(() => {
    //             loadAndDisplayHistogram(result.histogram_url, operation + 'Histogram', 'Обработанное изображение');
    //         }, 100);
    //     } else {
    //         console.error('URL гистограммы не указан для операции:', operation);
    //     }
    // }
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
        // ДОБАВЬТЕ ЭТОТ КОД ДЛЯ КОНТРАСТА
        const contrastSlider = document.getElementById('contrastSlider');
        const contrastValue = document.getElementById('contrastValue');
        if (contrastSlider) {
            contrastSlider.addEventListener('input', function() {
                contrastValue.textContent = this.value;
            });
        }

        // Загрузка и отображение гистограммы исходного изображения
        const originalHistogramUrl = document.getElementById('originalHistogramUrl');
        if (originalHistogramUrl && originalHistogramUrl.value) {
            console.log('Загрузка исходной гистограммы:', originalHistogramUrl.value);
            setTimeout(() => {
                loadAndDisplayHistogram(originalHistogramUrl.value, 'originalHistogram', 'Исходное изображение', true);
            }, 500);
        } else {
            console.error('URL исходной гистограммы не найден');
        }
    });

    // Функция применения операции
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
                // ИСПРАВЛЕННАЯ СТРОКА - используем ползунок вместо радиокнопок
                parameters.value = parseInt(document.getElementById('contrastSlider').value);
                break;
        }

        try {
            console.log('Отправка запроса на обработку:', { operation, parameters });

            const response = await fetch('{{ route("image.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    image_path: imagePath,
                    operation: operation,
                    parameters: parameters
                })
            });

            // Проверяем, является ли ответ JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Не JSON ответ:', text);
                throw new Error('Сервер вернул не JSON ответ. Возможно, произошла ошибка на сервере.');
            }

            const result = await response.json();
            console.log('Ответ сервера:', result);

            if (!response.ok) {
                throw new Error(result.error || 'Произошла ошибка при обработке изображения');
            }

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
    // function displayResult(operation, result) {
    //     const resultDiv = document.getElementById(operation + 'Result');
    //
    //     resultDiv.innerHTML = `
    //         <div class="image-comparison">
    //             <div class="image-comparison-item">
    //                 <h6>Обработанное изображение</h6>
    //                 <img src="${result.processed_url}" class="preview-image">
    //             </div>
    //             <div class="image-comparison-item">
    //                 <h6>Гистограмма обработанного изображения</h6>
    //                 <div class="histogram-container">
    //                     <canvas id="${operation}Histogram" width="400" height="200"></canvas>
    //                 </div>
    //             </div>
    //         </div>
    //     `;
    //
    //     // Загружаем и отображаем гистограмму обработанного изображения
    //     loadAndDisplayHistogram(result.histogram_url, operation + 'Histogram', 'Обработанное изображение');
    // }
</script>
</html>
