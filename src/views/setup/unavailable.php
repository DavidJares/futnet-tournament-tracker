<?php

declare(strict_types=1);

/** @var string $message */
?>
<div class="alert alert-warning" role="alert">
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<div>
    <a href="/" class="btn btn-outline-secondary">Back</a>
</div>