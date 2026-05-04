<?php

declare(strict_types=1);

$pageTitle = isset($title) && is_string($title) ? $title : 'FTT';
$appName = (string) ($config['app']['name'] ?? 'FTT');
$flashType = is_array($flash ?? null) ? (string) ($flash['type'] ?? '') : '';
$flashMessage = is_array($flash ?? null) ? (string) ($flash['message'] ?? '') : '';
$brandHref = $url('/');

if (is_array($currentSuperadmin ?? null)) {
    $brandHref = $url('/admin/dashboard');
} elseif (is_array($currentTournamentAdmin ?? null)) {
    $brandHref = $url('/tournament/' . (string) ($currentTournamentAdmin['slug'] ?? '') . '/admin');
}

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
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= htmlspecialchars($brandHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></a>
        <div id="js-header-clock" class="text-light small fw-semibold mx-auto pe-none" aria-label="Current time">--:--</div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if (is_array($currentSuperadmin ?? null)): ?>
                <span class="text-light small"><?= htmlspecialchars((string) $currentSuperadmin['username'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="<?= htmlspecialchars($url('/admin/logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                    <?= $csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            <?php elseif (is_array($currentTournamentAdmin ?? null)): ?>
                <span class="text-light small"><?= htmlspecialchars((string) ($currentTournamentAdmin['name'] ?? 'Tournament admin'), ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="<?= htmlspecialchars($url('/tournament/' . (string) ($currentTournamentAdmin['slug'] ?? '') . '/logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                    <?= $csrfField() ?>
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

    <?php
    ob_start();
    require $viewFile;
    $viewOutput = (string) ob_get_clean();
    $csrfInput = '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
    $viewOutput = preg_replace('/(<form\b[^>]*\bmethod\s*=\s*["\']post["\'][^>]*>)/i', '$1' . $csrfInput, $viewOutput) ?? $viewOutput;
    echo $viewOutput;
    ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    (function () {
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
    })();

    (function () {
        var clock = document.getElementById('js-header-clock');
        if (!clock) {
            return;
        }

        function pad(value) {
            return value < 10 ? '0' + value : String(value);
        }

        function renderClock() {
            var now = new Date();
            clock.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes());
        }

        renderClock();
        setInterval(renderClock, 1000);
    })();
</script>
</body>
</html>
