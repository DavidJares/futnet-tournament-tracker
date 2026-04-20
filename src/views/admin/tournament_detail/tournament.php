<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var list<string> $matchModes */
/** @var string $settingsActionUrl */

$tournamentId = (int) ($tournament['id'] ?? 0);
$startTimeValueRaw = (string) ($tournament['start_time'] ?? '');
$startTimeValue = '';
if (preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $startTimeValueRaw) === 1) {
    $startTimeValue = substr($startTimeValueRaw, 0, 5);
}
?>
<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($settingsActionUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="tournament">
            <div class="mb-2">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" required maxlength="150" value="<?= htmlspecialchars((string) ($tournament['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" required pattern="[a-z0-9-]+" maxlength="150" value="<?= htmlspecialchars((string) ($tournament['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="event_date" class="form-label">Event date</label>
                <input type="date" name="event_date" id="event_date" class="form-control" value="<?= htmlspecialchars((string) ($tournament['event_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="start_time" class="form-label">Start time</label>
                <input type="time" name="start_time" id="start_time" class="form-control" value="<?= htmlspecialchars($startTimeValue, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" id="location" class="form-control" maxlength="150" value="<?= htmlspecialchars((string) ($tournament['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="admin_password" class="form-label">Tournament admin password</label>
                <input type="password" name="admin_password" id="admin_password" class="form-control" minlength="8" placeholder="Leave blank to keep unchanged">
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label for="number_of_groups" class="form-label">Groups</label>
                    <input type="number" class="form-control" name="number_of_groups" id="number_of_groups" min="1" max="52" value="<?= (int) ($tournament['number_of_groups'] ?? 1) ?>" required>
                </div>
                <div class="col-6">
                    <label for="number_of_courts" class="form-label">Courts</label>
                    <input type="number" class="form-control" name="number_of_courts" id="number_of_courts" min="1" max="99" value="<?= (int) ($tournament['number_of_courts'] ?? 1) ?>" required>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6">
                    <label for="match_duration_minutes" class="form-label">Match duration (min)</label>
                    <input type="number" class="form-control" name="match_duration_minutes" id="match_duration_minutes" min="1" max="240" value="<?= (int) ($tournament['match_duration_minutes'] ?? 20) ?>" required>
                </div>
                <div class="col-6">
                    <label for="advancing_teams_count" class="form-label">Advancing teams</label>
                    <input type="number" class="form-control" name="advancing_teams_count" id="advancing_teams_count" min="1" max="64" value="<?= (int) ($tournament['advancing_teams_count'] ?? 2) ?>" required>
                </div>
            </div>
            <div class="mt-2 mb-3">
                <label for="match_mode" class="form-label">Match mode</label>
                <select class="form-select" name="match_mode" id="match_mode" required>
                    <?php foreach ($matchModes as $mode): ?>
                        <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($tournament['match_mode'] ?? '') === $mode) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save tournament settings</button>
        </form>
    </div>
</div>
