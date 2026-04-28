<?php

declare(strict_types=1);

$pageTitle = isset($title) && is_string($title) ? $title : 'Public View';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body { background: #f5f6f8; }
        .public-title { font-size: clamp(1.25rem, 2.6vw, 2.2rem); font-weight: 700; }
        .public-subtitle { font-size: clamp(1rem, 1.8vw, 1.35rem); }
        .public-table { font-size: clamp(1rem, 1.55vw, 1.28rem); }
        .public-table th, .public-table td { padding-top: 0.62rem; padding-bottom: 0.62rem; vertical-align: middle; }
        .public-table thead th { color: #1f2937; font-weight: 700; }
        .public-match-row { line-height: 1.35; }
        .public-winner-name { font-weight: 600; color: #3a7f5a; }
        .public-result-sub { color: #5f6b79; font-size: 0.88em; }
        .public-standings { table-layout: fixed; }
        .public-standings th, .public-standings td { text-align: center; }
        .public-standings th.col-team, .public-standings td.col-team { text-align: left; }
        .public-standings th.col-rank, .public-standings td.col-rank { width: 44px; }
        .public-standings th.col-team, .public-standings td.col-team { width: auto; }
        .public-standings th.col-num, .public-standings td.col-num { width: 58px; }
        .qr-box { max-width: 180px; }
    </style>
</head>
<body>
<main class="container-fluid px-3 px-md-4 py-3">
    <?php require $viewFile; ?>
</main>
</body>
</html>
