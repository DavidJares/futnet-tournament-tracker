<?php

declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}
$publicPath = $basePath . '/public/';
$target = $scheme . '://' . $host . $publicPath;
header('Location: ' . $target, true, 302);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($publicPath, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FTT</title>
</head>
<body>
    <main>
        <h1>Futnet Tournament Tracker</h1>
        <p><a href="<?= htmlspecialchars($publicPath . 'admin/login', ENT_QUOTES, 'UTF-8') ?>">Superadmin login</a></p>
    </main>
</body>
</html>
