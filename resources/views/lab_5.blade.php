<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Лабораторная работа №5 — Фильтрация изображений</title>

    <!-- CSRF token для JS -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Подключаем CSS файл -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        /* Стили для вкладок с цветом #57568c */
        .nav-tabs .nav-link {
            color: #57568c !important;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #57568c !important;
            font-weight: bold;
            background-color: #f8f9fa;
            border-bottom-color: #57568c !important;
        }

        .nav-tabs .nav-link:hover {
            color: #454472 !important;
            border-color: #e9ecef #e9ecef #57568c;
        }

        .step-card { border-left: 4px solid #007bff; margin-bottom: 10px; background-color: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .dictionary-item { padding: 4px 8px; margin: 3px; background-color: #f8f9fa; border-radius: 4px; font-family: monospace; display: inline-block; }
        pre.code-block { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }

        /* ДОБАВЬТЕ ЭТИ СТИЛИ */
        .card-body { color: #2c3e50; font-weight: 500; }

        /* Дополнительные стили для кнопок если нужно */
        .btn-custom {
            border: 1px solid #57568c !important;
            background-color: transparent;
            color: #57568c;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #57568c;
            color: white;
            transform: translateY(-1px);
        }

        .btn-primary-custom {
            border: 1px solid #57568c !important;
            background-color: #57568c;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background-color: #454472;
            border-color: #454472 !important;
            color: white;
            transform: translateY(-1px);
        }

        .image-comparison {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .image-container {
            flex: 1;
            text-align: center;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }

        .image-label {
            font-weight: bold;
            margin-bottom: 10px;
            color: #57568c;
        }

        .filter-params {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .noise-params {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Лабораторная работа №5 — Фильтрация изображений</h1>

    <nav class="mb-4">
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-noise-tab" data-bs-toggle="tab" data-bs-target="#nav-noise" type="button" role="tab">Наложение шума</button>
            <button class="nav-link" id="nav-filter-tab" data-bs-toggle="tab" data-bs-target="#nav-filter" type="button" role="tab">Фильтрация</button>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <!-- Наложение шума -->
        <div class="tab-pane fade show active" id="nav-noise" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <!-- Наложение шума -->
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Наложение шума на изображение</h5></div>
                        <div class="card-body">
                            <form id="noiseForm" action="/lab5/process-image" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Выберите изображение:</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" required>
                                </div>

                                <div class="noise-params">
                                    <div class="mb-3">
                                        <label class="form-label">Тип шума:</label>
                                        <select class="form-select" name="noise_type" id="noiseType">
                                            <option value="gaussian">Гауссов шум</option>
                                            <option value="salt_pepper">Шум "соль-перец"</option>
                                            <option value="uniform">Равномерный шум</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Интенсивность шума:</label>
                                        <input type="range" class="form-range" name="noise_intensity" id="noiseIntensity" min="0.01" max="0.5" step="0.01" value="0.1">
                                        <div class="form-text" id="intensityValue">0.1</div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary-custom" id="noiseBtn">Наложить шум</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Результаты наложения шума -->
                <div class="col-md-6">
                    <div id="noiseResults"></div>
                </div>
            </div>
        </div>

        <!-- Фильтрация -->
        <div class="tab-pane fade" id="nav-filter" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <!-- Применение фильтров -->
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Фильтрация изображения</h5></div>
                        <div class="card-body">
                            <form id="filterForm" action="/lab5/apply-filter" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Имя зашумленного изображения:</label>
                                    <input type="text" class="form-control" name="image_path" id="imagePath" placeholder="noisy_1234567890.png">
                                    <div class="form-text">Введите имя файла, полученного после наложения шума</div>
                                </div>

                                <div class="filter-params">
                                    <div class="mb-3">
                                        <label class="form-label">Тип фильтра:</label>
                                        <select class="form-select" name="filter_type" id="filterType">
                                            <option value="low_pass">Низкочастотный (сглаживающий)</option>
                                            <option value="high_pass">Высокочастотный (подчеркивание границ)</option>
                                            <option value="median">Медианный фильтр</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Размер маски:</label>
                                        <select class="form-select" name="mask_size" id="maskSize">
                                            <option value="3">3×3</option>
                                            <option value="5">5×5</option>
                                            <option value="7">7×7</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="maskTypeContainer">
                                        <label class="form-label">Тип маски:</label>
                                        <select class="form-select" name="mask_type" id="maskType">
                                            <option value="uniform">Равномерная</option>
                                            <option value="gaussian">Гауссова</option>
                                            <option value="laplacian">Лапласиан</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-custom" id="filterBtn">Применить фильтр</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Результаты фильтрации -->
                <div class="col-md-6">
                    <div id="filterResults"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Обновление значения интенсивности шума
        const noiseIntensity = document.getElementById('noiseIntensity');
        const intensityValue = document.getElementById('intensityValue');

        noiseIntensity.addEventListener('input', function() {
            intensityValue.textContent = this.value;
        });

        // Скрытие типа маски для медианного фильтра
        const filterType = document.getElementById('filterType');
        const maskTypeContainer = document.getElementById('maskTypeContainer');

        filterType.addEventListener('change', function() {
            if (this.value === 'median') {
                maskTypeContainer.style.display = 'none';
            } else {
                maskTypeContainer.style.display = 'block';
            }
        });

        // Универсальная функция отправки формы через fetch
        async function postFormUsingFetch(form, options = {}) {
            const url = form.getAttribute('action');
            const formData = new FormData(form);

            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };

            // Отключаем кнопку на время запроса
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.origText = submitButton.innerHTML;
                submitButton.innerHTML = 'Обработка...';
            }

            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers,
                    body: formData,
                    credentials: 'same-origin'
                });

                const text = await resp.text();
                try {
                    const data = text ? JSON.parse(text) : {};
                    if (!resp.ok) {
                        const msg = data.error ?? data.message ?? ('HTTP ' + resp.status);
                        throw new Error(msg);
                    }
                    return data;
                } catch (jsonErr) {
                    if (!resp.ok) {
                        throw new Error(text || ('HTTP ' + resp.status));
                    }
                    return { text };
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.dataset.origText || 'Отправить';
                }
            }
        }

        function showError(container, message) {
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(message)}</div>`;
        }

        function displayNoiseResults(data) {
            const container = document.getElementById('noiseResults');
            if (!data) { container.innerHTML = ''; return; }
            if (data.error) {
                showError(container, data.error);
                return;
            }

            let html = `<div class="alert alert-success">Шум успешно наложен!</div>`;
            html += `<p><strong>Тип шума:</strong> ${escapeHtml(data.noise_type)}</p>`;
            html += `<p><strong>Интенсивность:</strong> ${escapeHtml(String(data.noise_intensity))}</p>`;
            html += `<p><strong>Размер изображения:</strong> ${escapeHtml(String(data.width))} × ${escapeHtml(String(data.height))}</p>`;

            if (data.noisy_image) {
                const imageUrl = `/storage/${data.noisy_image.replace('public/', '')}`;
                html += `
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6>Зашумленное изображение</h6>
                        </div>
                        <div class="card-body text-center">
                            <img src="${imageUrl}"
                                 alt="Зашумленное изображение"
                                 class="img-fluid rounded border"
                                 style="max-height: 400px; object-fit: contain;">
                            <div class="mt-2">
                                <small class="text-muted">${data.width} × ${data.height} пикселей</small><br>
                                <small class="text-muted">Имя файла: ${escapeHtml(data.noisy_image)}</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Заполняем поле для фильтрации
            if (data.noisy_image) {
                document.getElementById('imagePath').value = data.noisy_image;
            }

            container.innerHTML = html;
        }

        function displayFilterResults(data) {
            const container = document.getElementById('filterResults');
            if (!data) { container.innerHTML = ''; return; }
            if (data.error) {
                showError(container, data.error);
                return;
            }

            let html = `<div class="alert alert-success">Фильтр успешно применен!</div>`;
            html += `<p><strong>Тип фильтра:</strong> ${escapeHtml(data.filter_type)}</p>`;
            html += `<p><strong>Размер маски:</strong> ${escapeHtml(String(data.mask_size))}×${escapeHtml(String(data.mask_size))}</p>`;

            if (data.mask_type) {
                html += `<p><strong>Тип маски:</strong> ${escapeHtml(data.mask_type)}</p>`;
            }

            if (data.filtered_image) {
                const filteredImageUrl = `/storage/${data.filtered_image.replace('public/', '')}`;
                const originalImagePath = document.getElementById('imagePath').value;
                const originalImageUrl = originalImagePath ? `/storage/${originalImagePath.replace('public/', '')}` : '';

                html += `
                    <div class="image-comparison mt-3">
                        ${originalImagePath ? `
                        <div class="image-container">
                            <div class="image-label">Исходное (с шумом)</div>
                            <img src="${originalImageUrl}"
                                 alt="Исходное изображение с шумом"
                                 class="img-fluid rounded border">
                        </div>
                        ` : ''}

                        <div class="image-container">
                            <div class="image-label">После фильтрации</div>
                            <img src="${filteredImageUrl}"
                                 alt="Отфильтрованное изображение"
                                 class="img-fluid rounded border">
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6>Информация о фильтрации</h6>
                            <p><strong>Фильтр:</strong> ${escapeHtml(this.getFilterDescription(data.filter_type))}</p>
                            <p><strong>Маска:</strong> ${escapeHtml(String(data.mask_size))}×${escapeHtml(String(data.mask_size))}</p>
                            ${data.mask_type ? `<p><strong>Тип маски:</strong> ${escapeHtml(data.mask_type)}</p>` : ''}
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        // Вспомогательная функция для описания фильтров
        function getFilterDescription(filterType) {
            const descriptions = {
                'low_pass': 'Низкочастотный фильтр (сглаживание) - подавляет высокочастотные шумы, но размывает границы',
                'high_pass': 'Высокочастотный фильтр - подчеркивает границы и детали, но усиливает шумы',
                'median': 'Медианный фильтр - эффективен против импульсных шумов, сохраняет границы'
            };
            return descriptions[filterType] || filterType;
        }

        /* ================== Events: attach handlers ================== */

        // Наложение шума
        document.getElementById('noiseForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('noiseResults');
            resultsContainer.innerHTML = '<div class="alert alert-info">Наложение шума...</div>';
            try {
                const data = await postFormUsingFetch(form);
                displayNoiseResults(data);
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка наложения шума');
            }
        });

        // Применение фильтра
        document.getElementById('filterForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('filterResults');
            resultsContainer.innerHTML = '<div class="alert alert-info">Применение фильтра...</div>';
            try {
                const data = await postFormUsingFetch(form);
                displayFilterResults(data);
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка применения фильтра');
            }
        });

    }); // DOMContentLoaded
</script>
</body>
</html>
