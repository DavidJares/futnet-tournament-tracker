<?php

declare(strict_types=1);

return [
    'version' => '20260428_000005_public_view_settings',
    'description' => 'Add public view settings and per-screen configuration for tournaments.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN public_view_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER knockout_mode",

        "ALTER TABLE tournaments
         ADD COLUMN autoplay_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER public_view_enabled",

        "ALTER TABLE tournaments
         ADD COLUMN rotation_interval_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 15 AFTER autoplay_enabled",

        "CREATE TABLE IF NOT EXISTS tournament_public_screens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            screen_key VARCHAR(50) NOT NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_public_screen_tournament_key (tournament_id, screen_key),
            KEY idx_public_screen_tournament_enabled_order (tournament_id, is_enabled, sort_order),
            CONSTRAINT fk_public_screens_tournament
                FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
