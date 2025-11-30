<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Лабораторная работа №4 — Алгоритм LZW</title>

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
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Лабораторная работа №4 — Алгоритм LZW</h1>

    <nav class="mb-4">
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-text-tab" data-bs-toggle="tab" data-bs-target="#nav-text" type="button" role="tab">Текст</button>
            <button class="nav-link" id="nav-image-tab" data-bs-toggle="tab" data-bs-target="#nav-image" type="button" role="tab">Изображение</button>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <!-- Текст -->
        <div class="tab-pane fade show active" id="nav-text" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <!-- Кодирование -->
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Кодирование текста</h5></div>
                        <div class="card-body">
                            <form id="encodeForm" action="/lab4/encode-text" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Введите текст для кодирования:</label>
                                    <textarea class="form-control" name="text" rows="4" placeholder="abacabadabacabae">abacabadabacabae</textarea>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="show_steps" id="showSteps">
                                    <label class="form-check-label" for="showSteps">Показать шаги выполнения</label>
                                </div>
                                <button type="submit" class="btn btn-primary-custom" id="encodeBtn">Закодировать</button>
                            </form>
                        </div>
                    </div>

                    <!-- Декодирование -->
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Декодирование</h5></div>
                        <div class="card-body">
                            <form id="decodeForm" action="/lab4/decode-text" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Введите закодированную последовательность (через пробел):</label>
                                    <input type="text" class="form-control" name="encoded_text" placeholder="0 1 0 2 5 0 3 9 8 6 4">
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="show_steps" id="showDecodeSteps">
                                    <label class="form-check-label" for="showDecodeSteps">Показать шаги выполнения</label>
                                </div>
                                <button type="submit" class="btn btn-custom" id="decodeBtn">Декодировать</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Результаты -->
                <div class="col-md-6">
                    <div id="results"></div>
                </div>
            </div>
        </div>

        <!-- Изображение -->
        <div class="tab-pane fade" id="nav-image" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Кодирование изображения</h5></div>
                        <div class="card-body">
                            <form id="imageForm" action="/lab4/encode-image" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Выберите изображение:</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-primary-custom" id="imageEncodeBtn">Закодировать изображение</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Декодирование изображения (JSON)</h5></div>
                        <div class="card-body">
                            <form id="imageDecodeForm" action="/lab4/decode-image" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Имя JSON-файла (в storage):</label>
                                    <input type="text" class="form-control" name="filename" placeholder="example_lzw_encoded.json">
                                    <div class="form-text">Например, файл, который вернул метод кодирования изображения.</div>
                                </div>
                                <button type="submit" class="btn btn-custom" id="imageDecodeBtn">Восстановить изображение</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div id="imageResults"></div>
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

        // Универсальная функция отправки формы через fetch (возвращает JSON или бросает ошибку с текстом)
        async function postFormUsingFetch(form, options = {}) {
            const url = form.getAttribute('action');
            const formData = new FormData(form);

            // Если чекбокс "show_steps" не установлен, он не будет в formData — это нормально.
            // Добавим заголовки
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
                // если ответ не JSON (например HTML страницы с редиректом), попробуем показать текст
                try {
                    const data = text ? JSON.parse(text) : {};
                    if (!resp.ok) {
                        // сервер ответил с ошибкой — перенесём сообщение
                        const msg = data.error ?? data.message ?? ('HTTP ' + resp.status);
                        throw new Error(msg);
                    }
                    return data;
                } catch (jsonErr) {
                    // Парсинг JSON не удался
                    if (!resp.ok) {
                        throw new Error(text || ('HTTP ' + resp.status));
                    }
                    // Если ok, но не JSON — вернём текст как объект
                    return { text };
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.dataset.origText || 'Отправить';
                }
            }
        }

        // Функции отображения (скопированы/упрощены из предыдущей версии)
        function showError(container, message) {
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(message)}</div>`;
        }

        function displayEncodeResults(data) {
            const container = document.getElementById('results');
            if (!data) { container.innerHTML = ''; return; }
            if (data.error) {
                showError(container, data.error);
                return;
            }

            const originalBits = data.original_bits ?? data.original_size_bits ?? 0;
            const encodedBits = data.encoded_bits ?? data.encoded_size_bits ?? 0;
            const compression = data.compression_ratio ?? 0;
            const encodedString = Array.isArray(data.encoded) ? data.encoded.join(' ') : (data.encoded_string ?? '');

            let html = `<div class="card stats-card mb-3"><div class="card-body">
                        <h5>Результаты кодирования</h5>
                        <p><strong>Исходный текст:</strong> ${escapeHtml(data.original ?? '')}</p>
                        <p><strong>Закодированная последовательность:</strong> <code>${escapeHtml(encodedString)}</code></p>
                        <p><strong>Биты до сжатия:</strong> ${escapeHtml(String(originalBits))}</p>
                        <p><strong>Биты после сжатия:</strong> ${escapeHtml(String(encodedBits))}</p>
                        <p><strong>Степень сжатия:</strong> ${escapeHtml(String(compression))}%</p>
                    </div></div>`;

            if (data.steps && data.steps.length) {
                html += `<div class="card mb-3"><div class="card-header"><h6>Шаги</h6></div><div class="card-body">`;
                data.steps.forEach((s, i) => {
                    html += `<div class="step-card p-2 mb-2"><strong>Шаг ${i+1}:</strong> ${escapeHtml(s.action || '')}`;
                    if (s.combined !== undefined) html += `<div>Комбинация: <code>${escapeHtml(String(s.combined))}</code></div>`;
                    if (s.in_dict !== undefined) html += `<div>В словаре: ${s.in_dict ? 'Да' : 'Нет'}</div>`;
                    if (s.output !== undefined) html += `<div>Вывод: <code>${escapeHtml(String(s.output))}</code></div>`;
                    if (s.output_code !== undefined) html += `<div>Вывод (код): <code>${escapeHtml(String(s.output_code))}</code></div>`;
                    if (s.added_symbol !== undefined) html += `<div>Добавлено: <code>${escapeHtml(String(s.added_symbol))}</code> → ${escapeHtml(String(s.added_code || ''))}</div>`;
                    html += `</div>`;
                });
                html += `</div></div>`;
            }

            if (data.dictionary && Object.keys(data.dictionary).length) {
                html += `<div class="card mb-3"><div class="card-header"><h6>Словарь (код → символ)</h6></div><div class="card-body">`;
                Object.entries(data.dictionary).forEach(([k,v]) => {
                    html += `<span class="dictionary-item">${escapeHtml(k)} → ${escapeHtml(v)}</span>`;
                });
                html += `</div></div>`;
            }

            container.innerHTML = html;
        }

        function displayDecodeResults(data) {
            const container = document.getElementById('results');
            if (!data) { container.innerHTML = ''; return; }
            if (data.error) { showError(container, data.error); return; }

            const encodedStr = Array.isArray(data.encoded) ? data.encoded.join(' ') : '';
            const decoded = data.decoded ?? '';

            let html = `<div class="card text-white mb-3"><div class="card-body">
                        <h5>Результат декодирования</h5>
                        <p><strong>Закодированная последовательность:</strong> <code>${escapeHtml(encodedStr)}</code></p>
                        <p><strong>Декодированный текст:</strong> ${escapeHtml(decoded)}</p>
                    </div></div>`;

            if (data.steps && data.steps.length) {
                html += `<div class="card mb-3"><div class="card-header"><h6>Шаги</h6></div><div class="card-body">`;
                data.steps.forEach((s, i) => {
                    html += `<div class="step-card p-2 mb-2"><strong>Шаг ${i+1}:</strong> ${escapeHtml(s.action || '')}`;
                    if (s.output !== undefined) html += `<div>Декодировано: <code>${escapeHtml(String(s.output))}</code></div>`;
                    if (s.new_entry !== undefined) html += `<div>Новая запись: ${escapeHtml(String(s.new_entry))}</div>`;
                    if (s.current_decoded !== undefined) html += `<div>Текущий: <code>${escapeHtml(String(s.current_decoded))}</code></div>`;
                    html += `</div>`;
                });
                html += `</div></div>`;
            }

            if (data.dictionary && Object.keys(data.dictionary).length) {
                html += `<div class="card mb-3"><div class="card-header"><h6>Словарь (код → символ)</h6></div><div class="card-body">`;
                Object.entries(data.dictionary).forEach(([k,v]) => {
                    html += `<span class="dictionary-item">${escapeHtml(k)} → ${escapeHtml(v)}</span>`;
                });
                html += `</div></div>`;
            }

            container.innerHTML = html;
        }

        function displayImageResults(data) {
            const container = document.getElementById('imageResults');
            if (!data) { container.innerHTML = ''; return; }
            if (data.error) { showError(container, data.error); return; }

            const originalBits = data.original_size_bits ?? data.original_size ?? 0;
            const encodedBits = data.encoded_size_bits ?? data.encoded_size ?? 0;
            const compression = data.compression_ratio ?? 0;

            let html = `<div class="card stats-card mb-3"><div class="card-body">
                <h5>Результаты сжатия изображения</h5>
                <p><strong>Биты до сжатия:</strong> ${escapeHtml(String(originalBits))}</p>
                <p><strong>Биты после сжатия:</strong> ${escapeHtml(String(encodedBits))}</p>
                <p><strong>Степень сжатия:</strong> ${escapeHtml(String(compression))}%</p>
                <p><strong>Пикселей:</strong> ${escapeHtml(String(data.pixels_count || 0))}</p>
                <p><strong>Кодов:</strong> ${escapeHtml(String(data.codes_count || 0))}</p>
                ${data.encoded_filename ? `<p><strong>JSON:</strong> ${escapeHtml(String(data.encoded_filename))}</p>` : ''}
            </div></div>`;

            // Если есть исходное изображение, показываем его
            if (data.original_image) {
                const imageUrl = `/storage/${data.original_image.replace('public/', '')}`;
                html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Исходное изображение</h6>
                </div>
                <div class="card-body text-center">
                    <img src="${imageUrl}"
                         alt="Исходное изображение"
                         class="img-fluid rounded border"
                         style="max-height: 300px; object-fit: contain;">
                </div>
            </div>
        `;
            }

            container.innerHTML = html;
        }

        /* ================== Events: attach handlers ================== */

        // Кодирование текста
        document.getElementById('encodeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('results');
            resultsContainer.innerHTML = '<div class="alert alert-info">Отправка запроса...</div>';
            try {
                const data = await postFormUsingFetch(form);
                displayEncodeResults(data);
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка запроса');
            }
        });

        // Декодирование текста
        document.getElementById('decodeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('results');
            resultsContainer.innerHTML = '<div class="alert alert-info">Отправка запроса...</div>';
            try {
                const data = await postFormUsingFetch(form);
                displayDecodeResults(data);
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка запроса');
            }
        });

        // Кодирование изображения
        document.getElementById('imageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('imageResults');
            resultsContainer.innerHTML = '<div class="alert alert-info">Отправка запроса...</div>';
            try {
                const data = await postFormUsingFetch(form);
                displayImageResults(data);
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка запроса');
            }
        });

        // Декодирование изображения
        // Декодирование изображения
        document.getElementById('imageDecodeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultsContainer = document.getElementById('imageResults');
            resultsContainer.innerHTML = '<div class="alert alert-info">Отправка запроса...</div>';
            try {
                const data = await postFormUsingFetch(form);
                if (data.error) {
                    showError(resultsContainer, data.error);
                    return;
                }

                // Показать успех и изображение
                let html = `<div class="alert alert-success">Изображение восстановлено: <strong>${escapeHtml(data.restored_image || '')}</strong></div>`;
                html += `<p>Размер: ${escapeHtml(String(data.width))} × ${escapeHtml(String(data.height))}, пикселей восстановлено: ${escapeHtml(String(data.pixels_restored || 0))}</p>`;

                // ДОБАВЛЯЕМ ОТОБРАЖЕНИЕ ИЗОБРАЖЕНИЯ
                if (data.restored_image) {
                    // Создаем URL для доступа к изображению из storage
                    const imageUrl = `/storage/${data.restored_image.replace('public/', '')}`;
                    html += `
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>Восстановленное изображение</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="${imageUrl}"
                             alt="Восстановленное изображение"
                             class="img-fluid rounded border"
                             style="max-height: 500px; object-fit: contain;">
                        <div class="mt-2">
                            <small class="text-muted">${data.width} × ${data.height} пикселей</small>
                        </div>
                    </div>
                </div>
            `;
                }

                resultsContainer.innerHTML = html;
            } catch (err) {
                console.error(err);
                showError(resultsContainer, err.message || 'Ошибка восстановления');
            }
        });

    }); // DOMContentLoaded
</script>
</body>
</html>
