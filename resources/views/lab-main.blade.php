<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторные работы по обработке цвета</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
            margin-bottom: 50px;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .header-section h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header-section .lead {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .lab-links-section {
            padding: 50px 0;
        }

        .lab-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }

        .lab-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .lab-card-1 {
            border-top: 5px solid #4facfe;
        }

        .lab-card-2 {
            border-top: 5px solid #ff6b6b;
        }

        .card-body {
            padding: 40px 30px;
            text-align: center;
        }

        .lab-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .lab-card-1 .lab-icon {
            color: #4facfe;
        }

        .lab-card-2 .lab-icon {
            color: #ff6b6b;
        }

        .lab-card h3 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .lab-card p {
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .btn-lab {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-block;
        }

        .btn-lab-1 {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
        }

        .btn-lab-1:hover {
            background: linear-gradient(45deg, #3a9bf7, #00d9e6);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(74, 172, 254, 0.4);
        }

        .btn-lab-2 {
            background: linear-gradient(45deg, #ff6b6b, #ffa36c);
            color: white;
        }

        .btn-lab-2:hover {
            background: linear-gradient(45deg, #ff5252, #ff8a50);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .features-section {
            background: #f8f9fa;
            padding: 40px 0;
            border-top: 1px solid #e9ecef;
        }

        .feature-item {
            text-align: center;
            padding: 20px;
        }

        .feature-icon {
            font-size: 2rem;
            color: #4facfe;
            margin-bottom: 15px;
        }

        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }

        .color-preview {
            width: 100%;
            height: 80px;
            border-radius: 10px;
            margin: 15px 0;
            border: 2px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 2rem;
            }

            .card-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-container">
        <!-- Заголовок -->
        <div class="header-section">
            <div class="container">
                <h1>Лабораторные работы</h1>
                <p class="lead">По обработке и преобразованию цветов</p>
            </div>
        </div>

        <!-- Ссылки на лабораторные работы -->
        <div class="lab-links-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5 col-md-6 mb-4">
                        <div class="lab-card lab-card-1">
                            <div class="card-body">
                                <div class="lab-icon">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <h3>Лабораторная работа 1</h3>
                                <p>Конвертер цветов между различными форматами: HEX, RGB, HSL. Изучение основных принципов преобразования цветовых моделей.</p>
                                <div class="color-preview" style="background: linear-gradient(45deg, #ff6b6b, #4facfe, #00f2fe);"></div>
                                <a href="{{ route('color.converter') }}" class="btn btn-lab btn-lab-1">
                                    Перейти к работе 1
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 col-md-6 mb-4">
                        <div class="lab-card lab-card-2">
                            <div class="card-body">
                                <div class="lab-icon">
                                    <i class="fas fa-sliders-h"></i>
                                </div>
                                <h3>Лабораторная работа 2</h3>
                                <p>Расширенный функционал работы с цветами: цветовые круги, координаты цвета, дополнительные преобразования и визуализация.</p>
                                <div class="color-preview" style="background: linear-gradient(45deg, #ff6b6b, #ffa36c, #ffe66d);"></div>
                                <a href="/lab2" class="btn btn-lab btn-lab-2">
                                    Перейти к работе 2
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Дополнительная информация -->
        <div class="features-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h5>Конвертация форматов</h5>
                        <p>Преобразование между HEX, RGB, HSL и другими цветовыми моделями</p>
                    </div>
                    <div class="col-md-4 feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-eye-dropper"></i>
                        </div>
                        <h5>Визуализация</h5>
                        <p>Наглядное представление цветов и их преобразований</p>
                    </div>
                    <div class="col-md-4 feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h5>Расширенный функционал</h5>
                        <p>Дополнительные инструменты для работы с цветовыми пространствами</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Футер -->
<div class="footer">
    <div class="container">
        <p>&copy; 2024 Лабораторные работы по обработке цвета. Все права защищены.</p>
    </div>
</div>

<!-- Font Awesome для иконок -->
<script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Альтернатива Font Awesome через CDN -->
<script>
    // Если Font Awesome не загрузился, добавляем базовые стили для иконок
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
                .fas::before {
                    font-family: 'Segoe UI', sans-serif;
                    font-weight: 900;
                }
                .fa-palette::before { content: '🎨'; }
                .fa-sliders-h::before { content: '⚙️'; }
                .fa-exchange-alt::before { content: '🔄'; }
                .fa-eye-dropper::before { content: '👁️'; }
                .fa-cogs::before { content: '🔧'; }
            `;
        document.head.appendChild(style);
    });
</script>
</body>
</html>
