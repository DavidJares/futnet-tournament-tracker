<?php

declare(strict_types=1);

/** @var string $qrUrl */
/** @var string $currentUrl */
?>
<div class="qr-box bb-public-qr">
    <div class="bb-public-qr-image">
        <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR code for this screen" class="img-fluid">
    </div>
    <div class="bb-public-qr-copy">
        <div>Open on phone</div>
        <a href="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>
