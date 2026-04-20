<?php

declare(strict_types=1);

$pageTitle = isset($title) && is_string($title) ? $title : 'FTT';
$appName = (string) ($config['app']['name'] ?? 'FTT');
$flashType = is_array($flash ?? null) ? (string) ($flash['type'] ?? '') : '';
$flashMessage = is_array($flash ?? null) ? (string) ($flash['message'] ?? '') : '';
$alertClass = 'alert-info';
if ($flashType === 'success') {
    $alertClass = 'alert-success';
}
if ($flashType === 'error') {
    $alertClass = 'alert-danger';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= htmlspecialchars($url('/admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if (is_array($currentSuperadmin ?? null)): ?>
                <span class="text-light small"><?= htmlspecialchars((string) $currentSuperadmin['username'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="<?= htmlspecialchars($url('/admin/logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                    <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container pb-4">
    <?php if ($flashMessage !== ''): ?>
        <div class="alert <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>" role="alert">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php require $viewFile; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
