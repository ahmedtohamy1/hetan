<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'عيد الخير') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts / Styles -->
        @vite(['resources/js/app.js'])

        <style>
            body {
                font-family: 'Cairo', sans-serif;
                margin: 0;
                padding: 0;
                padding-bottom: 40px; /* Space for the fixed footer */
            }

            .english-text {
                font-family: 'Inter', sans-serif;
            }

            .developer-credit {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 8px 16px;
                text-align: center;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                z-index: 9999;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .developer-credit:hover {
                background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
                transform: translateY(-2px);
                box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.2);
            }

            .developer-credit span {
                flex: 1;
            }

            .developer-credit i {
                font-size: 14px;
                opacity: 0.8;
            }

            @media (max-width: 768px) {
                .developer-credit {
                    font-size: 11px;
                    padding: 6px 12px;
                }

                .developer-credit span {
                    font-size: 11px;
                }
            }
        </style>
    </head>
    <body>
        <div id="app"></div>

        <!-- Developer Credit Sticky Footer -->
        <div class="developer-credit" onclick="window.open('https://fb.com/ahmed.tohamy.0', '_blank')">
            <span>تمت البرمجه بواسطه: أحمد تهامي Ahmed Tohamy</span>
            <i class="fab fa-facebook-f"></i>
        </div>
    </body>
</html>
