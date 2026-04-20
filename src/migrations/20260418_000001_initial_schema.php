<?php

declare(strict_types=1);

return [
    'version' => '20260418_000001_initial_schema',
    'description' => 'Create core tables for superadmin auth and tournament foundation.',
    'statements' => [
        "CREATE TABLE IF NOT EXISTS superadmins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tournaments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(150) NOT NULL UNIQUE,
            event_date DATE NULL,
            location VARCHAR(150) NULL,
            admin_password_hash VARCHAR(255) NOT NULL,
            number_of_groups TINYINT UNSIGNED NOT NULL DEFAULT 1,
            number_of_courts TINYINT UNSIGNED NOT NULL DEFAULT 1,
            match_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 20,
            advancing_teams_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
            match_mode ENUM('fixed_2_sets', 'best_of_3') NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tournament_groups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            name VARCHAR(20) NOT NULL,
            sort_order SMALLINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_tournament_groups_tournament_name (tournament_id, name),
            UNIQUE KEY uniq_tournament_groups_tournament_sort_order (tournament_id, sort_order),
            CONSTRAINT fk_tournament_groups_tournament
                FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS teams (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            team_name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_teams_tournament (tournament_id),
            CONSTRAINT fk_teams_tournament
                FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS matches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            stage ENUM('group', 'knockout') NOT NULL,
            group_id INT UNSIGNED NULL,
            round_name VARCHAR(100) NULL,
            bracket_position INT UNSIGNED NULL,
            team_a_id INT UNSIGNED NULL,
            team_b_id INT UNSIGNED NULL,
            team_a_source VARCHAR(255) NULL,
            team_b_source VARCHAR(255) NULL,
            court_number TINYINT UNSIGNED NULL,
            schedule_order INT UNSIGNED NULL,
            planned_start DATETIME NULL,
            status ENUM('pending', 'scheduled', 'in_progress', 'finished') NOT NULL DEFAULT 'pending',
            winner_team_id INT UNSIGNED NULL,
            sets_summary_a TINYINT UNSIGNED NOT NULL DEFAULT 0,
            sets_summary_b TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_matches_tournament (tournament_id),
            KEY idx_matches_stage (stage),
            KEY idx_matches_group (group_id),
            KEY idx_matches_schedule_order (schedule_order),
            KEY idx_matches_planned_start (planned_start),
            CONSTRAINT fk_matches_tournament
                FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
                ON DELETE CASCADE,
            CONSTRAINT fk_matches_group
                FOREIGN KEY (group_id) REFERENCES tournament_groups (id)
                ON DELETE SET NULL,
            CONSTRAINT fk_matches_team_a
                FOREIGN KEY (team_a_id) REFERENCES teams (id)
                ON DELETE SET NULL,
            CONSTRAINT fk_matches_team_b
                FOREIGN KEY (team_b_id) REFERENCES teams (id)
                ON DELETE SET NULL,
            CONSTRAINT fk_matches_winner
                FOREIGN KEY (winner_team_id) REFERENCES teams (id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS match_sets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            match_id INT UNSIGNED NOT NULL,
            set_number TINYINT UNSIGNED NOT NULL,
            score_a TINYINT UNSIGNED NOT NULL,
            score_b TINYINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_match_sets_match_set_number (match_id, set_number),
            CONSTRAINT fk_match_sets_match
                FOREIGN KEY (match_id) REFERENCES matches (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
