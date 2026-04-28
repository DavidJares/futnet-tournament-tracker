<?php

declare(strict_types=1);

return [
    'version' => '20260428_000007_public_map_embed_url',
    'description' => 'Add optional embedded map URL field for public overview.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN public_map_embed_url VARCHAR(500) NULL AFTER public_map_url",
    ],
];
