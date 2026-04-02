<?php
declare(strict_types=1);

/**
 * Auth Layout (for login/register/forgot pages)
 *
 * @var string $content
 * @var string $baseUrl
 * @var string|null $title
 */

$baseUrl = $baseUrl ?? '';
$title   = $title ?? 'Zoro';

$isDark = !empty($_SESSION['dark_mode']) && (int)$_SESSION['dark_mode'] === 1;
?>
<!DOCTYPE html>
<html lang="en"<?= $isDark ? ' data-theme="dark"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Base URL (subfolder-safe) -->
    <base href="<?= htmlspecialchars(rtrim($baseUrl, '/')) ?>/">

    <?php if ($isDark): ?>
        <link rel="stylesheet" id="zoro-dark-css" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/dark.css">
    <?php endif; ?>

    <!-- Minimal App Styles -->
    <style>
        :root {
            --bg: #ffffff;
            --fg: #111111;
            --muted: #6b7280;
            --border: #e5e7eb;
            --accent: #000000;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--fg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 24px;
        }

        .auth-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px;
            background: #fff;
        }

        .auth-header {
            margin-bottom: 20px;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 22px;
            font-weight: 600;
        }

        .auth-header p {
            margin-top: 6px;
            font-size: 14px;
            color: var(--muted);
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: var(--muted);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
        }

        button {
            width: 100%;
            padding: 11px;
            border-radius: 8px;
            border: none;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        .auth-footer {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }

        .auth-footer a {
            color: var(--fg);
            text-decoration: none;
            font-weight: 500;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <?= $content ?>
    </div>
</div>

</body>
</html>
