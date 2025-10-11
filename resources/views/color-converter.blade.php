<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конвертер цветовых моделей</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.8;
            font-size: 1.1em;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        .color-picker-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
        }

        .color-display {
            width: 100%;
            height: 120px;
            border-radius: 10px;
            margin: 20px 0;
            border: 3px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
        }

        .hex-display {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
        }

        .color-wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }

        .color-wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                from 90deg, /* Изменили с 0deg на 90deg */
                #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000
            );
            position: relative;
            cursor: crosshair;
        }

        .color-marker {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 10;
        }

        .saturation-value-picker {
            margin: 20px auto;
            width: 300px;
            height: 200px;
            position: relative;
            border: 3px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            cursor: crosshair;
        }

        .saturation-value-gradient {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .sv-marker {
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 10;
        }

        .picker-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: start;
        }

        .rgb-inputs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .input-group label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9em;
        }

        .input-group input {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .color-models {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .model-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .model-card:hover {
            transform: translateY(-2px);
        }

        .model-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .model-badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7em;
        }

        .model-values {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .value-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .value-row label {
            font-weight: 600;
            color: #495057;
            min-width: 40px;
        }

        .value-row input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            font-size: 0.9em;
            transition: border-color 0.3s ease;
        }

        .value-row input:focus {
            outline: none;
            border-color: #667eea;
        }

        .actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .color-wheel-container,
            .saturation-value-picker {
                width: 250px;
                height: 250px;
            }

            .saturation-value-picker {
                height: 150px;
            }

            .color-models {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎨 Конвертер цветовых моделей</h1>
        <p>RGB, CMYK, HSL, HSV, XYZ, Lab, YUV</p>
    </div>

    <div class="content">
        <div class="color-picker-section">
            <h2>Выбор цвета</h2>

            <div class="color-display" id="colorDisplay">
                <div class="hex-display" id="hexDisplay">#FF0000</div>
            </div>

            <div class="picker-container">
                <div class="color-wheel-container">
                    <div class="color-wheel" id="colorWheel"></div>
                    <div class="color-marker" id="colorMarker"></div>
                </div>

                <div class="saturation-value-picker" id="saturationValuePicker">
                    <div class="saturation-value-gradient" id="saturationValueGradient"></div>
                    <div class="sv-marker" id="svMarker"></div>
                </div>
            </div>

            <div class="rgb-inputs">
                <div class="input-group">
                    <label for="rInput">R</label>
                    <input type="number" id="rInput" min="0" max="255" value="255">
                </div>
                <div class="input-group">
                    <label for="gInput">G</label>
                    <input type="number" id="gInput" min="0" max="255" value="0">
                </div>
                <div class="input-group">
                    <label for="bInput">B</label>
                    <input type="number" id="bInput" min="0" max="255" value="0">
                </div>
            </div>
        </div>

        <div class="color-models-section">
            <h2>Цветовые модели</h2>
            <div class="color-models">
                <!-- RGB -->
                <div class="model-card">
                    <h3>RGB <span class="model-badge">основа</span></h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>R:</label>
                            <input type="number" id="rgbR" min="0" max="255" data-model="RGB" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>G:</label>
                            <input type="number" id="rgbG" min="0" max="255" data-model="RGB" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>B:</label>
                            <input type="number" id="rgbB" min="0" max="255" data-model="RGB" data-index="2">
                        </div>
                    </div>
                </div>

                <!-- CMYK -->
                <div class="model-card">
                    <h3>CMYK</h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>C:</label>
                            <input type="number" id="cmykC" min="0" max="100" data-model="CMYK" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>M:</label>
                            <input type="number" id="cmykM" min="0" max="100" data-model="CMYK" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>Y:</label>
                            <input type="number" id="cmykY" min="0" max="100" data-model="CMYK" data-index="2">
                        </div>
                        <div class="value-row">
                            <label>K:</label>
                            <input type="number" id="cmykK" min="0" max="100" data-model="CMYK" data-index="3">
                        </div>
                    </div>
                </div>

                <!-- HSL -->
                <div class="model-card">
                    <h3>HSL </h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>H:</label>
                            <input type="number" id="hslH" min="0" max="360" data-model="HSL" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>S:</label>
                            <input type="number" id="hslS" min="0" max="100" data-model="HSL" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>L:</label>
                            <input type="number" id="hslL" min="0" max="100" data-model="HSL" data-index="2">
                        </div>
                    </div>
                </div>

                <!-- HSV/HSB -->
                <div class="model-card">
                    <h3>HSV/HSB</h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>H:</label>
                            <input type="number" id="hsvH" min="0" max="360" data-model="HSV" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>S:</label>
                            <input type="number" id="hsvS" min="0" max="100" data-model="HSV" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>V:</label>
                            <input type="number" id="hsvV" min="0" max="100" data-model="HSV" data-index="2">
                        </div>
                    </div>
                </div>

                <!-- XYZ -->
                <div class="model-card">
                    <h3>XYZ</h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>X:</label>
                            <input type="number" id="xyzX" step="0.01" data-model="XYZ" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>Y:</label>
                            <input type="number" id="xyzY" step="0.01" data-model="XYZ" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>Z:</label>
                            <input type="number" id="xyzZ" step="0.01" data-model="XYZ" data-index="2">
                        </div>
                    </div>
                </div>

                <!-- Lab -->
                <div class="model-card">
                    <h3>Lab</h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>L:</label>
                            <input type="number" id="labL" step="0.01" data-model="LAB" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>a:</label>
                            <input type="number" id="labA" step="0.01" data-model="LAB" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>b:</label>
                            <input type="number" id="labB" step="0.01" data-model="LAB" data-index="2">
                        </div>
                    </div>
                </div>

                <!-- YUV -->
                <div class="model-card">
                    <h3>YUV</h3>
                    <div class="model-values">
                        <div class="value-row">
                            <label>Y:</label>
                            <input type="number" id="yuvY" data-model="YUV" data-index="0">
                        </div>
                        <div class="value-row">
                            <label>U:</label>
                            <input type="number" id="yuvU" data-model="YUV" data-index="1">
                        </div>
                        <div class="value-row">
                            <label>V:</label>
                            <input type="number" id="yuvV" data-model="YUV" data-index="2">
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" onclick="copyToClipboard()">📋 Копировать HEX</button>
                <button class="btn btn-secondary" onclick="resetAll()">🔄 Сбросить</button>
            </div>
        </div>
    </div>
</div>

<div class="notification" id="notification"></div>

<script>
    class ColorConverter {
        constructor() {
            this.currentRgb = [255, 0, 0];
            this.currentHsv = [0, 100, 100];
            this.isUpdating = false;
            this.init();
        }

        init() {
            this.setupColorWheel();
            this.setupSaturationValuePicker();
            this.setupInputListeners();
            this.setupModelInputListeners();
            this.updateSaturationValueGradient();
            this.updateAllValues();
        }

        setupInputListeners() {
            // Основные RGB поля
            ['rInput', 'gInput', 'bInput'].forEach(id => {
                document.getElementById(id).addEventListener('input', (e) => {
                    if (this.isUpdating) return;
                    const r = parseInt(document.getElementById('rInput').value) || 0;
                    const g = parseInt(document.getElementById('gInput').value) || 0;
                    const b = parseInt(document.getElementById('bInput').value) || 0;
                    this.setRgb(r, g, b);
                });
            });
        }

        setupModelInputListeners() {
            // Обработчики для всех полей цветовых моделей
            const modelInputs = document.querySelectorAll('input[data-model]');
            modelInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    if (this.isUpdating) return;

                    const model = e.target.getAttribute('data-model');
                    const index = parseInt(e.target.getAttribute('data-index'));
                    const value = parseFloat(e.target.value) || 0;

                    this.updateFromModel(model, index, value);
                });
            });
        }

        async updateFromModel(model, index, value) {
            try {
                // Получаем текущие значения модели
                const currentValues = this.getCurrentModelValues(model);
                currentValues[index] = value;

                // Отправляем на сервер для конвертации
                const response = await fetch('/color/convert-from-any', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        model: model,
                        values: currentValues
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.updateFromServerResponse(data);
                } else {
                    this.showNotification('Ошибка конвертации', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('Ошибка сети', 'error');
            }
        }

        getCurrentModelValues(model) {
            const modelInputs = {
                'RGB': ['rgbR', 'rgbG', 'rgbB'],
                'CMYK': ['cmykC', 'cmykM', 'cmykY', 'cmykK'],
                'HSL': ['hslH', 'hslS', 'hslL'],
                'HSV': ['hsvH', 'hsvS', 'hsvV'],
                'XYZ': ['xyzX', 'xyzY', 'xyzZ'],
                'LAB': ['labL', 'labA', 'labB'],
                'YUV': ['yuvY', 'yuvU', 'yuvV']
            };

            return modelInputs[model].map(id => {
                const input = document.getElementById(id);
                return parseFloat(input.value) || 0;
            });
        }

        updateFromServerResponse(data) {
            this.isUpdating = true;

            // Обновляем RGB
            this.currentRgb = data.colors.RGB;
            document.getElementById('rInput').value = this.currentRgb[0];
            document.getElementById('gInput').value = this.currentRgb[1];
            document.getElementById('bInput').value = this.currentRgb[2];

            // Обновляем все модели
            this.updateModelValues(data.colors);

            // Обновляем HSV для круга
            this.currentHsv = data.colors.HSV;
            this.updateSaturationValueGradient();
            this.updateMarkerPosition();

            // Обновляем HEX
            document.getElementById('hexDisplay').textContent = data.hex;
            document.getElementById('colorDisplay').style.backgroundColor = data.hex;

            this.isUpdating = false;
        }

        updateModelValues(colors) {
            // RGB
            document.getElementById('rgbR').value = colors.RGB[0];
            document.getElementById('rgbG').value = colors.RGB[1];
            document.getElementById('rgbB').value = colors.RGB[2];

            // CMYK
            document.getElementById('cmykC').value = Math.round(colors.CMYK[0]);
            document.getElementById('cmykM').value = Math.round(colors.CMYK[1]);
            document.getElementById('cmykY').value = Math.round(colors.CMYK[2]);
            document.getElementById('cmykK').value = Math.round(colors.CMYK[3]);

            // HSL
            document.getElementById('hslH').value = Math.round(colors.HSL[0]);
            document.getElementById('hslS').value = Math.round(colors.HSL[1]);
            document.getElementById('hslL').value = Math.round(colors.HSL[2]);

            // HSV
            document.getElementById('hsvH').value = Math.round(colors.HSV[0]);
            document.getElementById('hsvS').value = Math.round(colors.HSV[1]);
            document.getElementById('hsvV').value = Math.round(colors.HSV[2]);

            // XYZ
            document.getElementById('xyzX').value = colors.XYZ[0].toFixed(2);
            document.getElementById('xyzY').value = colors.XYZ[1].toFixed(2);
            document.getElementById('xyzZ').value = colors.XYZ[2].toFixed(2);

            // Lab
            document.getElementById('labL').value = colors.LAB[0].toFixed(2);
            document.getElementById('labA').value = colors.LAB[1].toFixed(2);
            document.getElementById('labB').value = colors.LAB[2].toFixed(2);

            // YUV
            document.getElementById('yuvY').value = Math.round(colors.YUV[0]);
            document.getElementById('yuvU').value = Math.round(colors.YUV[1]);
            document.getElementById('yuvV').value = Math.round(colors.YUV[2]);
        }

        setupColorWheel() {
            const wheel = document.getElementById('colorWheel');
            this.setupDrag(wheel, (e) => this.handleColorWheelClick(e));
        }

        setupSaturationValuePicker() {
            const picker = document.getElementById('saturationValuePicker');
            this.setupDrag(picker, (e) => this.handleSaturationValueClick(e));
        }

        setupDrag(element, clickHandler) {
            let isDragging = false;

            const startDrag = (e) => {
                isDragging = true;
                document.addEventListener('mousemove', onDrag);
                document.addEventListener('mouseup', stopDrag);
                clickHandler(e);
            };

            const onDrag = (e) => {
                if (!isDragging) return;
                clickHandler(e);
            };

            const stopDrag = () => {
                isDragging = false;
                document.removeEventListener('mousemove', onDrag);
                document.removeEventListener('mouseup', stopDrag);
            };

            element.addEventListener('mousedown', startDrag);
            element.addEventListener('click', clickHandler);
        }

        handleColorWheelClick(e) {
            const wheel = document.getElementById('colorWheel');
            const rect = wheel.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const dx = x - centerX;
            const dy = y - centerY;

            const angle = Math.atan2(dy, dx);
            let hue = (angle * 180 / Math.PI);
            if (hue < 0) hue += 360;

            const distance = Math.min(Math.sqrt(dx*dx + dy*dy), centerX);
            const saturation = distance / centerX;

            this.currentHsv[0] = hue;
            this.currentHsv[1] = saturation * 100;

            this.updateRgbFromHsv();
            this.updateSaturationValueGradient();
            this.updateMarkerPosition();
        }

        handleSaturationValueClick(e) {
            const picker = document.getElementById('saturationValuePicker');
            const rect = picker.getBoundingClientRect();
            const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
            const y = Math.max(0, Math.min(rect.height, e.clientY - rect.top));

            const saturation = x / rect.width;
            const value = 1 - (y / rect.height);

            this.currentHsv[1] = saturation * 100;
            this.currentHsv[2] = value * 100;

            this.updateRgbFromHsv();
            this.updateMarkerPosition();
        }

        updateSaturationValueGradient() {
            const gradient = document.getElementById('saturationValueGradient');
            const hue = this.currentHsv[0];

            gradient.style.background = `
        linear-gradient(to right,
            white 0%,
            hsl(${hue}, 100%, 50%) 100%),
        linear-gradient(to bottom,
            transparent 0%,
            black 100%)
    `;
            gradient.style.backgroundBlendMode = 'multiply';
        }

        updateMarkerPosition() {
            // SV маркер
            const svMarker = document.getElementById('svMarker');
            const picker = document.getElementById('saturationValuePicker');
            const rect = picker.getBoundingClientRect();

            const x = (this.currentHsv[1] / 100) * rect.width;
            const y = (1 - this.currentHsv[2] / 100) * rect.height;

            svMarker.style.left = x + 'px';
            svMarker.style.top = y + 'px';

            // Маркер цветового круга
            this.updateColorWheelMarker();
        }

        updateColorWheelMarker() {
            const marker = document.getElementById('colorMarker');
            const wheel = document.getElementById('colorWheel');
            const rect = wheel.getBoundingClientRect();
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const radius = centerX * (this.currentHsv[1] / 100);

            const angle = this.currentHsv[0] * Math.PI / 180;
            const x = centerX + radius * Math.cos(angle);
            const y = centerY + radius * Math.sin(angle);

            marker.style.left = x + 'px';
            marker.style.top = y + 'px';
        }

        async updateRgbFromHsv() {
            const rgb = this.hsvToRgb(this.currentHsv[0], this.currentHsv[1], this.currentHsv[2]);
            this.currentRgb = rgb;
            await this.updateAllValues();
        }

        async setRgb(r, g, b) {
            this.currentRgb = [
                Math.max(0, Math.min(255, r)),
                Math.max(0, Math.min(255, g)),
                Math.max(0, Math.min(255, b))
            ];

            this.currentHsv = this.rgbToHsv(...this.currentRgb);
            this.updateSaturationValueGradient();
            await this.updateAllValues();
        }

        async updateAllValues() {
            this.isUpdating = true;

            try {
                const response = await fetch('/color/convert-from-any', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        model: 'RGB',
                        values: this.currentRgb
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.updateFromServerResponse(data);
                } else {
                    this.showNotification('Ошибка сервера', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('Ошибка сети', 'error');
            }

            this.isUpdating = false;
        }

        // Вспомогательные функции для начальных вычислений
        rgbToHsv(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            const max = Math.max(r, g, b), min = Math.min(r, g, b);
            let h, s, v = max;
            const d = max - min;
            s = max === 0 ? 0 : d / max;

            if (max === min) {
                h = 0;
            } else {
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }

            return [h * 360, s * 100, v * 100];
        }

        hsvToRgb(h, s, v) {
            h /= 360; s /= 100; v /= 100;
            let r, g, b;
            const i = Math.floor(h * 6);
            const f = h * 6 - i;
            const p = v * (1 - s);
            const q = v * (1 - f * s);
            const t = v * (1 - (1 - f) * s);

            switch (i % 6) {
                case 0: r = v; g = t; b = p; break;
                case 1: r = q; g = v; b = p; break;
                case 2: r = p; g = v; b = t; break;
                case 3: r = p; g = q; b = v; break;
                case 4: r = t; g = p; b = v; break;
                case 5: r = v; g = p; b = q; break;
            }

            return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
        }

        showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    }

    // Глобальные функции
    async function resetAll() {
        colorConverter.currentHsv = [0, 100, 100];
        colorConverter.currentRgb = [255, 0, 0];

        colorConverter.updateSaturationValueGradient();
        await colorConverter.updateAllValues();

        colorConverter.showNotification('Значения сброшены');
    }

    async function copyToClipboard() {
        const hex = document.getElementById('hexDisplay').textContent;
        try {
            await navigator.clipboard.writeText(hex);
            colorConverter.showNotification('HEX скопирован в буфер');
        } catch (err) {
            colorConverter.showNotification('Ошибка копирования', 'error');
        }
    }

    // Инициализация
    const colorConverter = new ColorConverter();
</script>
</body>
</html>
