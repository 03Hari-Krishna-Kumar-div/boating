<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Dhanalakshmi Boating') }} - Boat Rental Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --clay-bg: #f0ebe3;
            --clay-card: #f5f0e8;
            --clay-shadow: 8px 8px 16px #d9d4cc, -8px -8px 16px #ffffff;
            --clay-shadow-sm: 4px 4px 8px #d9d4cc, -4px -4px 8px #ffffff;
            --clay-shadow-lg: 12px 12px 24px #d9d4cc, -12px -12px 24px #ffffff;
            --clay-inset: inset 3px 3px 6px #d9d4cc, inset -3px -3px 6px #ffffff;
            --clay-inset-sm: inset 2px 2px 4px #d9d4cc, inset -2px -2px 4px #ffffff;
            --clay-radius: 24px;
            --clay-radius-sm: 12px;
            --clay-primary: #6c5ce7;
            --clay-text: #2d3436;
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            background: var(--clay-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--clay-card);
            border-radius: var(--clay-radius);
            box-shadow: var(--clay-shadow-lg);
            border: none;
            padding: 40px;
            width: 100%;
            max-width: 420px;
        }

        .login-card .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-card .brand .icon {
            font-size: 3rem;
            color: var(--clay-primary);
        }

        .login-card .brand h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--clay-text);
            margin-top: 10px;
        }

        .login-card .brand p {
            color: #636e72;
            font-size: 0.9rem;
        }

        .clay-input {
            background: var(--clay-bg);
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-inset-sm);
            padding: 14px 16px;
            color: var(--clay-text);
            transition: all 0.3s ease;
            width: 100%;
        }

        .clay-input:focus {
            box-shadow: var(--clay-inset);
            outline: none;
        }

        .clay-btn {
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            transition: all 0.3s ease;
            font-weight: 600;
            padding: 12px 28px;
            width: 100%;
        }

        .clay-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--clay-shadow);
        }

        .clay-btn:active {
            box-shadow: var(--clay-inset-sm);
            transform: translateY(1px);
        }

        .clay-btn-primary {
            background: var(--clay-primary);
            color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--clay-text);
            margin-bottom: 6px;
        }

        .error-feedback {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <i class="bi bi-boat-fill icon"></i>
            <h1>Dhanalakshmi Boating</h1>
            <p>Boat Rental Management System</p>
        </div>

        {{ $slot }}
    </div>

    @stack('scripts')
</body>
</html>
