<?php

declare(strict_types=1);

/** @var string $tournamentName */
?>
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm" style="max-width: 520px;">
        <div class="card-body text-center p-4">
            <h1 class="h3 mb-2"><?= htmlspecialchars($tournamentName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-0">Public View is currently unavailable for this tournament.</p>
        </div>
    </div>
</div>
