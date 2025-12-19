<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Лабораторная работа №5 - Фильтрация изображений</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 1rem 0;
        }
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            background: #f5f5f5;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-container img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }
        .control-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-gradient {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
            color: white;
        }
        .noise-type, .filter-type {
            cursor: pointer;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin: 5px;
            transition: all 0.3s;
        }
        .noise-type:hover, .filter-type:hover {
            border-color: #6a11cb;
            background-color: #f8f9ff;
        }
        .noise-type.active, .filter-type.active {
            border-color: #6a11cb;
            background-color: #6a11cb;
            color: white;
        }
        .processing-status {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px;
            border-radius: 10px;
            z-index: 1000;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .result-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            background: white;
        }
        .result-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .step-indicator:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background: white;
            padding: 10px 20px;
            border-radius: 50px;
            border: 2px solid #e9ecef;
            font-weight: 600;
            color: #6c757d;
        }
        .step.active {
            background: #6a11cb;
            color: white;
            border-color: #6a11cb;
        }
        .step.completed {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="container">
        <h1 class="text-center mb-3"><i class="fas fa-filter me-2"></i>Лабораторная работа №5</h1>
        <h3 class="text-center">«Фильтрация изображений»</h3>
{{--        <p class="text-center mt-3">Изучение и программная реализация алгоритмов фильтрации и улучшения контраста изображений</p>--}}
    </div>
</div>

<div class="container">
    <!-- Шаги выполнения -->
    <div class="step-indicator">
        <div class="step active" id="step1">1. Загрузка</div>
        <div class="step" id="step2">2. Добавление шума</div>
        <div class="step" id="step3">3. Фильтрация</div>
        <div class="step" id="step4">4. Результаты</div>
    </div>

    <!-- Шаг 1: Загрузка изображения -->
    <div class="card" id="step1-content">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-upload me-2"></i>Шаг 1: Загрузка изображения</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="control-panel">
                        <div class="mb-3">
                            <label for="imageUpload" class="form-label">Выберите изображение</label>
                            <input type="file" class="form-control" id="imageUpload" accept="image/*">
                            <div class="form-text">Поддерживаемые форматы: JPG, PNG, GIF (до 5MB)</div>
                        </div>
                        <button type="button" class="btn btn-gradient w-100" id="uploadBtn">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Загрузить изображение
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="image-container">
                        <div id="previewArea" class="text-center p-5">
                            <i class="fas fa-image fa-5x text-muted mb-3"></i>
                            <p class="text-muted">Предпросмотр изображения</p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <p id="imageInfo" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="button" class="btn btn-gradient" id="nextStep1" disabled>
                    Далее <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Шаг 2: Добавление шума -->
    <div class="card d-none" id="step2-content">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0"><i class="fas fa-wave-square me-2"></i>Шаг 2: Добавление цифрового шума</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="control-panel">
                        <h5>Тип шума</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="noise-type" data-type="gaussian">
                                    <i class="fas fa-cloud me-2"></i>Гауссов
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="noise-type" data-type="white">
                                    <i class="fas fa-snowflake me-2"></i>Белый
                                </div>
                            </div>
{{--                            <div class="col-6">--}}
{{--                                <div class="noise-type" data-type="perlin">--}}
{{--                                    <i class="fas fa-water me-2"></i>Перлина--}}
{{--                                </div>--}}
{{--                            </div>--}}
                            <div class="col-6">
                                <div class="noise-type" data-type="voronoi">
                                    <i class="fas fa-th-large me-2"></i>Вороного
                                </div>
                            </div>
{{--                            <div class="col-6">--}}
{{--                                <div class="noise-type" data-type="curl">--}}
{{--                                    <i class="fas fa-wind me-2"></i>Вихревой--}}
{{--                                </div>--}}
{{--                            </div>--}}
                        </div>

                        <div class="mt-4">
                            <label for="noiseIntensity" class="form-label">
                                Интенсивность шума: <span id="intensityValue">50</span>%
                            </label>
                            <input type="range" class="form-range" id="noiseIntensity" min="1" max="100" value="50">
                        </div>

                        <button type="button" class="btn btn-gradient w-100 mt-3" id="applyNoiseBtn">
                            <i class="fas fa-play me-2"></i>Применить шум
                        </button>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary w-100" id="prevStep2">
                                <i class="fas fa-arrow-left me-2"></i>Назад
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Оригинальное изображение</h6>
                            <div class="image-container">
                                <img id="originalImage" src="" alt="" class="d-none">
                                <div id="originalPlaceholder" class="text-center p-3">
                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">Оригинал</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Изображение с шумом</h6>
                            <div class="image-container">
                                <img id="noisedImage" src="" alt="" class="d-none">
                                <div id="noisedPlaceholder" class="text-center p-3">
                                    <i class="fas fa-wave-square fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">С шумом</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
{{--                        <h6>Описание типов шума:</h6>--}}
                        <div class="row">
                            <div class="col-12">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>Гауссов шум:</strong> Равномерный шум с нормальным распределением
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Белый шум:</strong> Случайные точки по всему изображению
                                    </li>
{{--                                    <li class="list-group-item">--}}
{{--                                        <strong>Шум Перлина:</strong> Плавный, естественный шум--}}
{{--                                    </li>--}}
                                    <li class="list-group-item">
                                        <strong>Шум Вороного:</strong> Ячеистый шум, основанный на диаграмме Вороного
                                    </li>
{{--                                    <li class="list-group-item">--}}
{{--                                        <strong>Вихревой шум:</strong> Вихреобразные искажения--}}
{{--                                    </li>--}}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="button" class="btn btn-gradient" id="nextStep2" disabled>
                    Далее <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Шаг 3: Фильтрация -->
    <div class="card d-none" id="step3-content">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-filter me-2"></i>Шаг 3: Фильтрация изображений</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="control-panel">
                        <h5>Тип фильтра</h5>
                        <div class="mb-3">
                            <div class="filter-type active" data-type="lowpass">
                                <i class="fas fa-snowflake me-2"></i>Низкочастотный
                                <small class="d-block text-muted">Сглаживание, подавление шума</small>
                            </div>
                            <div class="filter-type" data-type="highpass">
                                <i class="fas fa-mountain me-2"></i>Высокочастотный
                                <small class="d-block text-muted">Подчеркивание границ</small>
                            </div>
                            <div class="filter-type" data-type="median">
                                <i class="fas fa-chart-line me-2"></i>Медианный
                                <small class="d-block text-muted">Подавление импульсных помех</small>
                            </div>
                        </div>

                        <div id="lowpassOptions">
                            <h6>Маски НЧ-фильтра</h6>
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="lowpassMask" id="h1" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="h1">H1</label>

                                <input type="radio" class="btn-check" name="lowpassMask" id="h2" autocomplete="off">
                                <label class="btn btn-outline-primary" for="h2">H2</label>

                                <input type="radio" class="btn-check" name="lowpassMask" id="h3" autocomplete="off">
                                <label class="btn btn-outline-primary" for="h3">H3</label>
                            </div>
                        </div>

                        <div id="highpassOptions" class="d-none">
                            <h6>Маски ВЧ-фильтра</h6>
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="highpassMask" id="h4" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="h4">H4</label>

                                <input type="radio" class="btn-check" name="highpassMask" id="h5" autocomplete="off">
                                <label class="btn btn-outline-primary" for="h5">H5</label>

                                <input type="radio" class="btn-check" name="highpassMask" id="h6" autocomplete="off">
                                <label class="btn btn-outline-primary" for="h6">H6</label>
                            </div>

                            <div class="mb-3">
                                <label for="brightnessFactor" class="form-label">
                                    Коэффициент яркости: <span id="brightnessValue">4</span>
                                </label>
                                <input type="range" class="form-range" id="brightnessFactor" min="0" max="10" value="4" step="0.1">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="maskSize" class="form-label">Размер маски: <span id="sizeValue">3</span>×<span id="sizeValue2">3</span></label>
                            <input type="range" class="form-range" id="maskSize" min="3" max="11" value="3" step="2">
                        </div>

                        <button type="button" class="btn btn-gradient w-100" id="applyFilterBtn">
                            <i class="fas fa-play me-2"></i>Применить фильтр
                        </button>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary w-100" id="prevStep3">
                                <i class="fas fa-arrow-left me-2"></i>Назад
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>С шумом</h6>
                            <div class="image-container">
                                <img id="filterInputImage" src="" alt="" class="d-none">
                                <div id="filterInputPlaceholder" class="text-center p-3">
                                    <i class="fas fa-wave-square fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">Входное изображение</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>После фильтрации</h6>
                            <div class="image-container">
                                <img id="filteredImage" src="" alt="" class="d-none">
                                <div id="filteredPlaceholder" class="text-center p-3">
                                    <i class="fas fa-filter fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">Результат</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Описание фильтров:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Низкочастотный</h6>
                                        <p class="small">Убирает высокочастотные компоненты, сглаживает изображение</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Высокочастотный</h6>
                                        <p class="small">Подчеркивает границы и детали изображения</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Медианный</h6>
                                        <p class="small">Эффективен против импульсных помех, сохраняет границы</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="button" class="btn btn-gradient" id="nextStep3">
                    Результаты <i class="fas fa-chart-bar ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Шаг 4: Результаты -->
    <div class="card d-none" id="step4-content">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Шаг 4: Результаты и сравнение</h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="control-panel">
                        <h5>Действия</h5>
                        <button type="button" class="btn btn-gradient w-100 mb-2" id="refreshResults">
                            <i class="fas fa-sync me-2"></i>Обновить результаты
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100 mb-2" id="clearAllBtn">
                            <i class="fas fa-trash me-2"></i>Очистить все
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" id="prevStep4">
                            <i class="fas fa-arrow-left me-2"></i>Назад
                        </button>
                    </div>
                </div>
                <div class="col-md-8">
                    <h5>Анализ эффективности фильтрации</h5>
                    <p>Сравните эффективность различных фильтров и масок для устранения разных типов шума.</p>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Рекомендации:</strong>
                        <ul class="mb-0">
                            <li>Для гауссова и белого шума эффективны низкочастотные фильтры</li>
                            <li>Для импульсного шума используйте медианный фильтр</li>
                            <li>Высокочастотные фильтры подчеркивают детали, но усиливают шум</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h5>Все обработанные изображения</h5>
            <div id="resultsContainer" class="results-grid">
                <!-- Результаты будут загружены здесь -->
            </div>

            <div class="mt-4">
                <h5>Сравнение масок</h5>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Тип фильтра</th>
                        <th>Маска</th>
                        <th>Описание</th>
                        <th>Эффективность</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>НЧ</td>
                        <td>H1</td>
                        <td>Единичная маска 3×3</td>
                        <td>Хорошо сглаживает, но размывает границы</td>
                    </tr>
                    <tr>
                        <td>НЧ</td>
                        <td>H2</td>
                        <td>Маска с усиленным центром</td>
                        <td>Сохраняет больше деталей чем H1</td>
                    </tr>
                    <tr>
                        <td>НЧ</td>
                        <td>H3</td>
                        <td>Гауссова маска</td>
                        <td>Наилучшее качество сглаживания</td>
                    </tr>
                    <tr>
                        <td>ВЧ</td>
                        <td>H4</td>
                        <td>Простая ВЧ маска</td>
                        <td>Хорошо подчеркивает границы</td>
                    </tr>
                    <tr>
                        <td>ВЧ</td>
                        <td>H5</td>
                        <td>Агрессивная ВЧ маска</td>
                        <td>Сильное подчеркивание деталей</td>
                    </tr>
                    <tr>
                        <td>ВЧ</td>
                        <td>H6</td>
                        <td>ВЧ маска с коррекцией</td>
                        <td>Баланс детализации и шума</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Статус обработки -->
    <div id="processingStatus" class="processing-status d-none">
        <div class="text-center">
            <div class="spinner-border text-light mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 id="processingMessage">Обработка...</h5>
        </div>
    </div>
</div>

<footer class="mt-5 py-3 bg-dark text-white">
    <div class="container text-center">
        <p class="mb-0">Алгоритмические основы мультимедийных технологий &copy; 2024</p>
        <p class="small">Лабораторная работа №5 - Фильтрация изображений</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Глобальные переменные
    let currentStep = 1;
    let currentOriginalImage = null;
    let selectedNoiseType = 'gaussian';
    let selectedFilterType = 'lowpass';

    // Настройка jQuery для отправки CSRF токена
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Инициализация
    $(document).ready(function() {
        loadCurrentImages();

        // Шаг 1: Загрузка изображения
        $('#uploadBtn').click(uploadImage);
        $('#nextStep1').click(() => goToStep(2));

        // Шаг 2: Добавление шума
        $('.noise-type').click(function() {
            $('.noise-type').removeClass('active');
            $(this).addClass('active');
            selectedNoiseType = $(this).data('type');
        });
        $('.noise-type[data-type="gaussian"]').addClass('active');

        $('#noiseIntensity').on('input', function() {
            $('#intensityValue').text($(this).val());
        });

        $('#applyNoiseBtn').click(applyNoise);
        $('#prevStep2').click(() => goToStep(1));
        $('#nextStep2').click(() => goToStep(3));

        // Шаг 3: Фильтрация
        $('.filter-type').click(function() {
            $('.filter-type').removeClass('active');
            $(this).addClass('active');
            selectedFilterType = $(this).data('type');

            $('#lowpassOptions, #highpassOptions').addClass('d-none');
            if (selectedFilterType === 'lowpass') {
                $('#lowpassOptions').removeClass('d-none');
            } else if (selectedFilterType === 'highpass') {
                $('#highpassOptions').removeClass('d-none');
            }
        });

        $('#maskSize').on('input', function() {
            const size = $(this).val();
            $('#sizeValue, #sizeValue2').text(size);
        });

        $('#brightnessFactor').on('input', function() {
            $('#brightnessValue').text($(this).val());
        });

        $('#applyFilterBtn').click(applyFilter);
        $('#prevStep3').click(() => goToStep(2));
        $('#nextStep3').click(() => goToStep(4));

        // Шаг 4: Результаты
        $('#refreshResults').click(loadResults);
        $('#clearAllBtn').click(clearAll);
        $('#prevStep4').click(() => goToStep(3));
    });

    // Навигация по шагам
    function goToStep(step) {
        $('.step').removeClass('active completed');

        for (let i = 1; i <= 4; i++) {
            $(`#step${i}-content`).addClass('d-none');
            if (i < step) {
                $(`#step${i}`).addClass('completed');
            }
        }

        $(`#step${step}`).addClass('active');
        $(`#step${step}-content`).removeClass('d-none');
        currentStep = step;

        if (step === 4) {
            loadResults();
        }
    }

    // Загрузка изображения на сервер
    function uploadImage() {
        const fileInput = $('#imageUpload')[0];
        if (!fileInput.files.length) {
            alert('Пожалуйста, выберите файл изображения');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileInput.files[0]);

        showProcessing('Загрузка изображения...');

        $.ajax({
            url: '{{ route("lab5.upload") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideProcessing();
                if (response.success) {
                    alert('Изображение успешно загружено. Все предыдущие изображения удалены.');
                    loadCurrentImages();

                    // Обновляем превью
                    $('#previewArea').html(`<img src="${response.preview_path}" class="img-fluid" alt="Preview">`);
                    $('#imageInfo').text(`Файл: ${response.filename}`);

                    // Обновляем оригинальное изображение на шаге 2
                    $('#originalImage').attr('src', response.preview_path).removeClass('d-none');
                    $('#originalPlaceholder').addClass('d-none');

                    currentOriginalImage = response.filename;
                    $('#nextStep1').prop('disabled', false);
                    $('#applyNoiseBtn').prop('disabled', false);
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                hideProcessing();
                if (xhr.status === 419) {
                    alert('Ошибка CSRF токена. Пожалуйста, обновите страницу и попробуйте снова.');
                } else {
                    alert('Ошибка при загрузке изображения');
                }
                console.error(xhr.responseText);
            }
        });
    }

    // Загрузка текущих изображений
    function loadCurrentImages() {
        $.ajax({
            url: '{{ route("lab5.images") }}',
            type: 'GET',
            success: function(response) {
                if (response.success && response.images.length > 0) {
                    // Находим оригинальное изображение
                    const originalImage = response.images.find(img => img.type === 'original');
                    if (originalImage) {
                        // Обновляем превью
                        $('#previewArea').html(`<img src="${originalImage.url}" class="img-fluid" alt="Preview">`);
                        $('#imageInfo').text(`Файл: ${originalImage.name}`);

                        // Обновляем оригинальное изображение на шаге 2
                        $('#originalImage').attr('src', originalImage.url).removeClass('d-none');
                        $('#originalPlaceholder').addClass('d-none');

                        currentOriginalImage = originalImage.name;
                        $('#nextStep1').prop('disabled', false);
                        $('#applyNoiseBtn').prop('disabled', false);
                    }

                    // Находим изображение с шумом
                    const noisedImage = response.images.find(img => img.type === 'noised');
                    if (noisedImage) {
                        $('#noisedImage').attr('src', noisedImage.url).removeClass('d-none');
                        $('#noisedPlaceholder').addClass('d-none');
                        $('#filterInputImage').attr('src', noisedImage.url).removeClass('d-none');
                        $('#filterInputPlaceholder').addClass('d-none');
                        $('#nextStep2').prop('disabled', false);
                    }

                    // Находим отфильтрованное изображение
                    const filteredImage = response.images.find(img => img.type === 'filtered');
                    if (filteredImage) {
                        $('#filteredImage').attr('src', filteredImage.url).removeClass('d-none');
                        $('#filteredPlaceholder').addClass('d-none');
                    }
                } else {
                    $('#previewArea').html(`
                        <i class="fas fa-image fa-5x text-muted mb-3"></i>
                        <p class="text-muted">Предпросмотр изображения</p>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Ошибка при загрузке изображений:', xhr.responseText);
            }
        });
    }

    // Применение шума
    function applyNoise() {
        const intensity = $('#noiseIntensity').val();

        showProcessing(`Применение ${selectedNoiseType} шума...`);

        $.ajax({
            url: '{{ route("lab5.apply-noise") }}',
            type: 'POST',
            data: {
                noise_type: selectedNoiseType,
                intensity: intensity
            },
            success: function(response) {
                hideProcessing();
                if (response.success) {
                    // Обновляем изображение с шумом
                    $('#noisedImage').attr('src', response.image_path).removeClass('d-none');
                    $('#noisedPlaceholder').addClass('d-none');

                    // Обновляем входное изображение для фильтрации
                    $('#filterInputImage').attr('src', response.image_path).removeClass('d-none');
                    $('#filterInputPlaceholder').addClass('d-none');

                    $('#nextStep2').prop('disabled', false);

                    alert('Шум успешно применен');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                hideProcessing();
                if (xhr.status === 419) {
                    alert('Ошибка CSRF токена. Пожалуйста, обновите страницу и попробуйте снова.');
                } else if (xhr.status === 404) {
                    alert('Сначала загрузите изображение');
                } else {
                    alert('Ошибка при применении шума');
                }
                console.error(xhr.responseText);
            }
        });
    }

    // Применение фильтра
    function applyFilter() {
        const maskSize = $('#maskSize').val();
        let maskType = '';
        let brightnessFactor = 1;

        if (selectedFilterType === 'lowpass') {
            maskType = $('input[name="lowpassMask"]:checked').attr('id');
        } else if (selectedFilterType === 'highpass') {
            maskType = $('input[name="highpassMask"]:checked').attr('id');
            brightnessFactor = $('#brightnessFactor').val();
        } else {
            maskType = 'custom';
        }

        showProcessing(`Применение ${selectedFilterType} фильтра...`);

        $.ajax({
            url: '{{ route("lab5.apply-filter") }}',
            type: 'POST',
            data: {
                filter_type: selectedFilterType,
                mask_type: maskType,
                mask_size: maskSize,
                brightness_factor: brightnessFactor
            },
            success: function(response) {
                hideProcessing();
                if (response.success) {
                    // Обновляем отфильтрованное изображение
                    $('#filteredImage').attr('src', response.image_path).removeClass('d-none');
                    $('#filteredPlaceholder').addClass('d-none');

                    $('#nextStep3').prop('disabled', false);

                    alert('Фильтр успешно применен');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                hideProcessing();
                if (xhr.status === 419) {
                    alert('Ошибка CSRF токена. Пожалуйста, обновите страницу и попробуйте снова.');
                } else if (xhr.status === 404) {
                    alert('Сначала загрузите изображение и/или примените шум');
                } else {
                    alert('Ошибка при применении фильтра');
                }
                console.error(xhr.responseText);
            }
        });
    }

    // Загрузка результатов
    function loadResults() {
        showProcessing('Загрузка результатов...');

        $.ajax({
            url: '{{ route("lab5.images") }}',
            type: 'GET',
            success: function(response) {
                hideProcessing();
                if (response.success) {
                    const container = $('#resultsContainer');
                    container.empty();

                    if (response.images.length === 0) {
                        container.html('<div class="text-center text-muted p-5">Нет обработанных изображений</div>');
                        return;
                    }

                    response.images.forEach(image => {
                        let typeBadge = '';
                        let icon = '';
                        let title = '';

                        switch(image.type) {
                            case 'original':
                                typeBadge = '<span class="badge bg-primary">Оригинал</span>';
                                icon = 'fa-image';
                                title = 'Текущее оригинальное изображение';
                                break;
                            case 'noised':
                                typeBadge = '<span class="badge bg-warning text-dark">С шумом</span>';
                                icon = 'fa-wave-square';
                                title = 'Текущее изображение с шумом';
                                break;
                            case 'filtered':
                                typeBadge = '<span class="badge bg-success">Отфильтровано</span>';
                                icon = 'fa-filter';
                                title = 'Текущее отфильтрованное изображение';
                                break;
                        }

                        const card = `
                            <div class="result-card">
                                <div class="mb-2">
                                    ${typeBadge}
                                    <span class="badge bg-secondary float-end">${(image.size / 1024).toFixed(1)} KB</span>
                                </div>
                                <img src="${image.url}" alt="${title}" class="mb-2" title="${title}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-truncate" title="${title}">
                                        <i class="fas ${icon} me-1"></i> ${title}
                                    </small>
                                    <button class="btn btn-sm btn-outline-primary" onclick="downloadImage('${image.url}', '${image.name}')">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        container.append(card);
                    });
                }
            },
            error: function(xhr) {
                hideProcessing();
                alert('Ошибка при загрузке результатов');
                console.error(xhr.responseText);
            }
        });
    }

    // Очистка всех изображений
    function clearAll() {
        if (!confirm('Вы уверены, что хотите удалить все изображения?')) {
            return;
        }

        showProcessing('Очистка...');

        $.ajax({
            url: '{{ route("lab5.clear") }}',
            type: 'POST',
            data: {},
            success: function(response) {
                hideProcessing();
                if (response.success) {
                    alert('Все изображения удалены');

                    // Сбрасываем интерфейс
                    $('#previewArea').html(`
                        <i class="fas fa-image fa-5x text-muted mb-3"></i>
                        <p class="text-muted">Предпросмотр изображения</p>
                    `);
                    $('#imageInfo').text('');

                    // Сбрасываем шаг 2
                    $('#originalImage').addClass('d-none');
                    $('#originalPlaceholder').removeClass('d-none');
                    $('#noisedImage').addClass('d-none');
                    $('#noisedPlaceholder').removeClass('d-none');

                    // Сбрасываем шаг 3
                    $('#filterInputImage').addClass('d-none');
                    $('#filterInputPlaceholder').removeClass('d-none');
                    $('#filteredImage').addClass('d-none');
                    $('#filteredPlaceholder').removeClass('d-none');

                    // Сбрасываем кнопки
                    $('#nextStep1').prop('disabled', true);
                    $('#applyNoiseBtn').prop('disabled', true);
                    $('#nextStep2').prop('disabled', true);
                    $('#nextStep3').prop('disabled', true);

                    loadResults();
                }
            },
            error: function(xhr) {
                hideProcessing();
                if (xhr.status === 419) {
                    alert('Ошибка CSRF токена. Пожалуйста, обновите страницу и попробуйте снова.');
                } else {
                    alert('Ошибка при очистке');
                }
                console.error(xhr.responseText);
            }
        });
    }

    // Скачивание изображения
    function downloadImage(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Управление статусом обработки
    function showProcessing(message) {
        $('#processingMessage').text(message);
        $('#processingStatus').removeClass('d-none');
    }

    function hideProcessing() {
        $('#processingStatus').addClass('d-none');
    }
</script>
</body>
</html>
