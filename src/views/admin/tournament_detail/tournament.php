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
$modeLabels = [
    'fixed_2_sets' => 'Fixed 2 sets',
    'best_of_3' => 'Best of 3',
];
$currentSlug = (string) ($tournament['slug'] ?? '');
$currentName = (string) ($tournament['name'] ?? '');
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$loginPath = $url('/tournament/' . $currentSlug . '/login');
$currentLoginUrl = $scheme . '://' . $host . $loginPath;
?>
<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($settingsActionUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="tournament">
            <div class="mb-2">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" required maxlength="150" value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>" data-original-name="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
                <label for="slug_display" class="form-label">Slug</label>
                <div class="input-group">
                    <input type="text" id="slug_display" class="form-control bg-body-tertiary text-muted border-secondary-subtle" readonly value="<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>" aria-readonly="true">
                    <button type="button" class="btn btn-outline-secondary js-copy-slug" data-copy-target="slug_display">Copy</button>
                </div>
                <div class="form-text">Slug is read-only by default.</div>
            </div>
            <div class="mb-2">
                <label for="tournament_login_url" class="form-label">Tournament admin login URL</label>
                <div class="input-group">
                    <input
                        type="text"
                        id="tournament_login_url"
                        class="form-control bg-body-tertiary text-muted border-secondary-subtle"
                        readonly
                        value="<?= htmlspecialchars($currentLoginUrl, ENT_QUOTES, 'UTF-8') ?>"
                        aria-readonly="true"
                    >
                    <button type="button" class="btn btn-outline-secondary js-copy-slug" data-copy-target="tournament_login_url">Copy</button>
                    <a id="tournament_login_url_open" class="btn btn-outline-secondary" href="<?= htmlspecialchars($currentLoginUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open</a>
                </div>
            </div>
            <div id="js-slug-options" class="mb-2 d-none">
                <div class="alert alert-warning py-2 mb-2 small" role="alert">
                    Name change suggests a new slug: <code id="js-proposed-slug">-</code>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="slug_update_action" id="slug_update_action_keep" value="keep" checked>
                    <label class="form-check-label" for="slug_update_action_keep">Keep current slug</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="slug_update_action" id="slug_update_action_update" value="update">
                    <label class="form-check-label" for="slug_update_action_update">Update slug to match tournament name</label>
                </div>
            </div>
            <input type="hidden" value="keep" id="slug_update_action_fallback">
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
                    <label for="advancing_teams_count" class="form-label d-inline-flex align-items-center gap-1">
                        <span>Advancing teams</span>
                        <span
                            class="badge rounded-pill text-bg-light border text-secondary"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-title="2, 4, 8, 16 create a standard bracket. Other counts use byes for top seeds. Example N=6: seeds 1 and 2 receive a bye, then 3 vs 6 and 4 vs 5."
                            style="cursor: help;"
                            aria-label="Advancing teams help"
                        >i</span>
                    </label>
                    <input type="number" class="form-control" name="advancing_teams_count" id="advancing_teams_count" min="1" max="64" value="<?= (int) ($tournament['advancing_teams_count'] ?? 2) ?>" required>
                </div>
            </div>
            <div class="row g-2 mt-1 mb-3">
                <div class="col-6">
                    <label for="group_stage_mode" class="form-label">Group stage mode</label>
                    <select class="form-select" name="group_stage_mode" id="group_stage_mode" required>
                        <?php foreach ($matchModes as $mode): ?>
                            <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($tournament['group_stage_mode'] ?? ($tournament['match_mode'] ?? 'fixed_2_sets')) === $mode) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($modeLabels[$mode] ?? $mode), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label for="knockout_mode" class="form-label">Knockout mode</label>
                    <select class="form-select" name="knockout_mode" id="knockout_mode" required>
                        <?php foreach ($matchModes as $mode): ?>
                            <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($tournament['knockout_mode'] ?? 'best_of_3') === $mode) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($modeLabels[$mode] ?? $mode), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save tournament settings</button>
        </form>
    </div>
</div>
<script>
    (function () {
        var nameInput = document.getElementById('name');
        var slugInput = document.getElementById('slug_display');
        var slugOptions = document.getElementById('js-slug-options');
        var proposedSlug = document.getElementById('js-proposed-slug');
        var keepOption = document.getElementById('slug_update_action_keep');
        var updateOption = document.getElementById('slug_update_action_update');
        var fallbackAction = document.getElementById('slug_update_action_fallback');
        var loginUrlInput = document.getElementById('tournament_login_url');
        var loginUrlOpen = document.getElementById('tournament_login_url_open');
        var loginUrlPrefix = <?= json_encode($scheme . '://' . $host . $url('/tournament/'), JSON_UNESCAPED_SLASHES) ?>;
        var loginUrlSuffix = '/login';
        var currentSlug = slugInput ? (slugInput.value || '').trim() : '';
        var originalName = nameInput ? (nameInput.getAttribute('data-original-name') || '').trim() : '';

        var slugify = function (value) {
            return (value || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-+/g, '-');
        };

        var syncSlugChoice = function () {
            if (!nameInput || !slugOptions || !proposedSlug || !keepOption || !updateOption || !fallbackAction) {
                return;
            }
            var newName = (nameInput.value || '').trim();
            var generated = slugify(newName);
            var nameChanged = newName !== originalName;
            var slugWouldChange = generated !== '' && generated !== currentSlug;
            var showChoice = nameChanged && slugWouldChange;
            slugOptions.classList.toggle('d-none', !showChoice);
            proposedSlug.textContent = generated !== '' ? generated : 'tournament';
            if (!showChoice) {
                keepOption.checked = true;
                fallbackAction.name = 'slug_update_action';
                fallbackAction.value = 'keep';
                syncLoginUrlPreview(currentSlug);
                return;
            }
            fallbackAction.removeAttribute('name');
            syncLoginUrlPreview(updateOption.checked ? (generated !== '' ? generated : 'tournament') : currentSlug);
        };

        var syncLoginUrlPreview = function (slugValue) {
            if (!loginUrlInput || !loginUrlOpen) {
                return;
            }

            var safeSlug = (slugValue || '').trim();
            if (safeSlug === '') {
                safeSlug = 'tournament';
            }

            var fullUrl = loginUrlPrefix + safeSlug + loginUrlSuffix;
            loginUrlInput.value = fullUrl;
            loginUrlOpen.setAttribute('href', fullUrl);
        };

        if (nameInput) {
            nameInput.addEventListener('input', syncSlugChoice);
        }
        if (keepOption) {
            keepOption.addEventListener('change', syncSlugChoice);
        }
        if (updateOption) {
            updateOption.addEventListener('change', syncSlugChoice);
        }
        syncLoginUrlPreview(currentSlug);
        syncSlugChoice();

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
                var value = input.value || '';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value);
                    return;
                }
                input.focus();
                input.select();
                document.execCommand('copy');
            });
        });
    })();
</script>
