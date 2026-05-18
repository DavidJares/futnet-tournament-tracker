<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $tournaments */
/** @var list<string> $matchModes */

$modeLabels = [
    'fixed_2_sets' => 'Fixed 2 sets',
    'best_of_3' => 'Best of 3',
];
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$origin = $scheme . '://' . $host;
$today = new \DateTimeImmutable('today');
$totalTournaments = count($tournaments);
$upcomingTournaments = 0;
$publicViewEnabledCount = 0;
foreach ($tournaments as $tournament) {
    $eventDateRaw = trim((string) ($tournament['event_date'] ?? ''));
    if ($eventDateRaw !== '') {
        $eventDate = \DateTimeImmutable::createFromFormat('Y-m-d', $eventDateRaw);
        if ($eventDate instanceof \DateTimeImmutable && $eventDate >= $today) {
            $upcomingTournaments++;
        }
    }

    if ((int) ($tournament['public_view_enabled'] ?? 0) > 0) {
        $publicViewEnabledCount++;
    }
}

$sortableDateValue = static function (array $tournament, string $field): int {
    $value = trim((string) ($tournament[$field] ?? ''));
    if ($value === '') {
        return 0;
    }

    $timestamp = strtotime($value);
    return is_int($timestamp) ? $timestamp : 0;
};

usort(
    $tournaments,
    static function (array $a, array $b) use ($sortableDateValue): int {
        $eventA = $sortableDateValue($a, 'event_date');
        $eventB = $sortableDateValue($b, 'event_date');
        if ($eventA > 0 && $eventB > 0 && $eventA !== $eventB) {
            return $eventB <=> $eventA;
        }
        if ($eventA > 0 && $eventB <= 0) {
            return -1;
        }
        if ($eventB > 0 && $eventA <= 0) {
            return 1;
        }

        $createdCompare = $sortableDateValue($b, 'created_at') <=> $sortableDateValue($a, 'created_at');
        if ($createdCompare !== 0) {
            return $createdCompare;
        }

        return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
    }
);
?>
<section class="bb-dashboard-shell">
    <header class="bb-workspace-header bb-dashboard-header">
        <div>
            <span class="bb-section-kicker">Superadmin</span>
            <h2>Tournaments</h2>
            <p>Manage tournaments, admin access and public displays.</p>
        </div>
        <a href="#create-tournament" class="btn btn-primary">Create tournament</a>
    </header>

    <section class="bb-metric-grid" aria-label="Tournament summary">
        <div class="bb-metric-card">
            <span>Total tournaments</span>
            <strong><?= $totalTournaments ?></strong>
        </div>
        <div class="bb-metric-card">
            <span>Upcoming</span>
            <strong><?= $upcomingTournaments ?></strong>
        </div>
        <div class="bb-metric-card">
            <span>Public enabled</span>
            <strong><?= $publicViewEnabledCount ?></strong>
        </div>
        <div class="bb-metric-card">
            <span>Draft/private</span>
            <strong><?= max(0, $totalTournaments - $publicViewEnabledCount) ?></strong>
        </div>
    </section>

    <section class="bb-dashboard-section">
        <div class="bb-board-section-heading">
            <div>
                <span class="bb-section-kicker">Overview</span>
                <h3>Tournament Control Center</h3>
            </div>
        </div>

        <?php if ($totalTournaments === 0): ?>
            <div class="bb-empty-state">No tournaments yet. Create the first tournament below.</div>
        <?php else: ?>
            <div class="bb-dashboard-toolbar">
                <label for="dashboard_tournament_search" class="visually-hidden">Search tournaments</label>
                <input type="search" class="form-control" id="dashboard_tournament_search" placeholder="Search tournaments..." autocomplete="off">
                <span id="dashboard_tournament_count"><?= $totalTournaments ?> tournament<?= $totalTournaments === 1 ? '' : 's' ?></span>
            </div>

            <div class="bb-dashboard-list" id="dashboard_tournament_list">
                <?php foreach ($tournaments as $tournament): ?>
                    <?php
                    $tournamentId = (int) ($tournament['id'] ?? 0);
                    $name = (string) ($tournament['name'] ?? '');
                    $slug = (string) ($tournament['slug'] ?? '');
                    $eventDate = trim((string) ($tournament['event_date'] ?? ''));
                    $startTimeRaw = trim((string) ($tournament['start_time'] ?? ''));
                    $startTime = $startTimeRaw !== '' ? substr($startTimeRaw, 0, 5) : '';
                    $location = trim((string) ($tournament['location'] ?? ''));
                    $groupMode = (string) ($tournament['group_stage_mode'] ?? ($tournament['match_mode'] ?? ''));
                    $knockoutMode = (string) ($tournament['knockout_mode'] ?? 'best_of_3');
                    $groupModeLabel = (string) ($modeLabels[$groupMode] ?? $groupMode);
                    $knockoutModeLabel = (string) ($modeLabels[$knockoutMode] ?? $knockoutMode);
                    $detailUrl = $url('/admin/tournament?id=' . $tournamentId);
                    $adminLoginUrl = $origin . $url('/tournament/' . $slug . '/login');
                    $publicDisplayUrl = $origin . $url('/public/' . $slug . '/display');
                    $publicViewEnabled = (int) ($tournament['public_view_enabled'] ?? 0) > 0;
                    $searchText = strtolower(trim($name . ' ' . $slug . ' ' . $location));
                    ?>
                    <article class="bb-tournament-row" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="bb-tournament-row-main">
                            <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="bb-tournament-row-slug">
                                <code><?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code>
                            </div>
                            <div class="bb-tournament-row-meta">
                                <span><?= htmlspecialchars($eventDate !== '' ? $eventDate : 'No date', ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars($startTime !== '' ? $startTime : 'No start', ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars($location !== '' ? $location : 'No location', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="bb-tournament-row-badges" aria-label="Tournament status and rules">
                            <span class="badge <?= $publicViewEnabled ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $publicViewEnabled ? 'Public' : 'Private' ?></span>
                            <div class="bb-tournament-mode-group">
                                <span class="bb-tournament-mode-pill">Group: <?= htmlspecialchars($groupModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="bb-tournament-mode-pill">KO: <?= htmlspecialchars($knockoutModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="bb-tournament-actions">
                            <div class="bb-action-group" aria-label="Tournament actions">
                                <a class="btn btn-sm btn-primary bb-tournament-primary-action" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">Detail</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-dashboard-copy" data-copy-value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Copy slug</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-dashboard-copy" data-copy-value="<?= htmlspecialchars($adminLoginUrl, ENT_QUOTES, 'UTF-8') ?>">Copy admin URL</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-dashboard-copy" data-copy-value="<?= htmlspecialchars($publicDisplayUrl, ENT_QUOTES, 'UTF-8') ?>">Copy display URL</button>
                                <form method="post" action="<?= htmlspecialchars($url('/admin/tournaments/delete'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Delete this tournament and all related data?');">
                                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                    <input type="hidden" name="confirm_delete" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="bb-empty-state d-none" id="dashboard_tournament_empty">No tournaments match your search.</div>
        <?php endif; ?>
    </section>

    <section id="create-tournament" class="bb-create-tournament-panel">
        <div class="bb-workspace-card-header">
            <div>
                <span class="bb-section-kicker">Create</span>
                <h3>Create Tournament</h3>
                <p>Set up the tournament shell, access password, structure and scoring rules.</p>
            </div>
        </div>

        <form method="post" action="<?= htmlspecialchars($url('/admin/tournaments/create'), ENT_QUOTES, 'UTF-8') ?>" class="bb-create-tournament-form">
            <section class="bb-create-form-section">
                <div class="bb-create-form-section-head">
                    <span>01</span>
                    <div>
                        <h4>Basic Information</h4>
                        <p>Name, slug preview and event details.</p>
                    </div>
                </div>
                <div class="bb-create-form-grid">
                    <div class="bb-field bb-field-full">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="name" required maxlength="150" autocomplete="off">
                    </div>
                    <div class="bb-field bb-field-full">
                        <label for="slug" class="form-label">Slug</label>
                        <div class="bb-copy-group">
                            <input type="text" class="form-control" name="slug" id="slug" readonly maxlength="150" aria-readonly="true">
                            <button type="button" class="btn btn-outline-secondary js-copy-slug" data-copy-target="slug">Copy</button>
                        </div>
                        <div class="form-text">Auto-generated from tournament name. Unique suffix will be added automatically if needed.</div>
                    </div>
                    <div class="bb-field">
                        <label for="event_date" class="form-label">Event date</label>
                        <input type="date" class="form-control" name="event_date" id="event_date">
                    </div>
                    <div class="bb-field">
                        <label for="start_time" class="form-label">Start time</label>
                        <input type="time" class="form-control" name="start_time" id="start_time" value="09:00">
                    </div>
                    <div class="bb-field bb-field-full">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" id="location" maxlength="150">
                    </div>
                </div>
            </section>

            <section class="bb-create-form-section">
                <div class="bb-create-form-section-head">
                    <span>02</span>
                    <div>
                        <h4>Access</h4>
                        <p>Password for the tournament admin login.</p>
                    </div>
                </div>
                <div class="bb-create-form-grid bb-create-form-grid-single">
                    <div class="bb-field">
                        <label for="admin_password" class="form-label">Tournament admin password</label>
                        <input type="password" class="form-control" name="admin_password" id="admin_password" required minlength="8">
                    </div>
                </div>
            </section>

            <section class="bb-create-form-section">
                <div class="bb-create-form-section-head">
                    <span>03</span>
                    <div>
                        <h4>Structure</h4>
                        <p>Groups, courts, pacing and bracket size.</p>
                    </div>
                </div>
                <div class="bb-create-form-grid bb-create-form-grid-compact">
                    <div class="bb-field">
                        <label for="number_of_groups" class="form-label">Groups</label>
                        <input type="number" class="form-control" name="number_of_groups" id="number_of_groups" min="1" max="52" value="2" required>
                    </div>
                    <div class="bb-field">
                        <label for="number_of_courts" class="form-label">Courts</label>
                        <input type="number" class="form-control" name="number_of_courts" id="number_of_courts" min="1" max="99" value="1" required>
                    </div>
                    <div class="bb-field">
                        <label for="match_duration_minutes" class="form-label">Match duration</label>
                        <input type="number" class="form-control" name="match_duration_minutes" id="match_duration_minutes" min="1" max="240" value="20" required>
                    </div>
                    <div class="bb-field">
                        <label for="advancing_teams_count" class="form-label">Advancing teams</label>
                        <input type="number" class="form-control" name="advancing_teams_count" id="advancing_teams_count" min="1" max="64" value="2" required>
                    </div>
                </div>
            </section>

            <section class="bb-create-form-section">
                <div class="bb-create-form-section-head">
                    <span>04</span>
                    <div>
                        <h4>Rules</h4>
                        <p>Match modes for group and knockout play.</p>
                    </div>
                </div>
                <div class="bb-create-form-grid">
                    <div class="bb-field">
                        <label for="group_stage_mode" class="form-label">Group stage mode</label>
                        <select class="form-select" name="group_stage_mode" id="group_stage_mode" required>
                            <?php foreach ($matchModes as $mode): ?>
                                <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= $mode === 'fixed_2_sets' ? 'selected' : '' ?>><?= htmlspecialchars((string) ($modeLabels[$mode] ?? $mode), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bb-field">
                        <label for="knockout_mode" class="form-label">Knockout mode</label>
                        <select class="form-select" name="knockout_mode" id="knockout_mode" required>
                            <?php foreach ($matchModes as $mode): ?>
                                <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= $mode === 'best_of_3' ? 'selected' : '' ?>><?= htmlspecialchars((string) ($modeLabels[$mode] ?? $mode), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <div class="bb-dashboard-savebar">
                <div>
                    <strong>Ready to create?</strong>
                    <span>The tournament opens in detail view after creation.</span>
                </div>
                <button type="submit" class="btn btn-primary">Create tournament</button>
            </div>
        </form>
    </section>
</section>

<script>
    (function () {
        var nameInput = document.getElementById('name');
        var slugInput = document.getElementById('slug');
        if (nameInput && slugInput) {
            var slugify = function (value) {
                return (value || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-+/g, '-');
            };
            var syncSlug = function () {
                slugInput.value = slugify(nameInput.value || '');
            };
            nameInput.addEventListener('input', syncSlug);
            syncSlug();
        }

        var copyText = function (value, fallbackInput) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value);
                return;
            }
            if (fallbackInput) {
                fallbackInput.focus();
                fallbackInput.select();
                document.execCommand('copy');
            }
        };

        document.querySelectorAll('.js-copy-slug').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-copy-target');
                if (!targetId) {
                    return;
                }
                var input = document.getElementById(targetId);
                if (!input) {
                    return;
                }
                copyText(input.value || '', input);
            });
        });

        document.querySelectorAll('.js-dashboard-copy').forEach(function (button) {
            button.addEventListener('click', function () {
                copyText(button.getAttribute('data-copy-value') || '', null);
            });
        });

        var searchInput = document.getElementById('dashboard_tournament_search');
        var list = document.getElementById('dashboard_tournament_list');
        var emptyState = document.getElementById('dashboard_tournament_empty');
        var countLabel = document.getElementById('dashboard_tournament_count');
        if (searchInput && list) {
            var rows = Array.prototype.slice.call(list.querySelectorAll('.bb-tournament-row'));
            var syncSearch = function () {
                var query = (searchInput.value || '').trim().toLowerCase();
                var visibleCount = 0;
                rows.forEach(function (row) {
                    var searchable = row.getAttribute('data-search') || '';
                    var visible = query === '' || searchable.indexOf(query) !== -1;
                    row.hidden = !visible;
                    if (visible) {
                        visibleCount++;
                    }
                });
                if (emptyState) {
                    emptyState.classList.toggle('d-none', visibleCount > 0);
                }
                if (countLabel) {
                    countLabel.textContent = visibleCount + ' tournament' + (visibleCount === 1 ? '' : 's');
                }
            };

            searchInput.addEventListener('input', syncSearch);
            syncSearch();
        }
    })();
</script>
