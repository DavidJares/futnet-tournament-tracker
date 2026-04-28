<?php

declare(strict_types=1);

/** @var string $qrUrl */
/** @var string $currentUrl */
?>
<div class="card qr-box shadow-sm">
    <div class="card-body p-2 text-center">
        <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR code for this screen" class="img-fluid rounded border">
        <div class="small text-muted mt-1">Open on phone</div>
        <div class="small"><a href="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?></a></div>
    </div>
</div>
