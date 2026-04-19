<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var list<array<string, mixed>> $groups */
/** @var list<array<string, mixed>> $teams */
/** @var array{
 *     total_teams: int,
 *     group_count: int,
 *     unassigned_count: int,
 *     teams_per_group: array<int, int>,
 *     grouped_teams: array<int, list<array<string, mixed>>>,
 *     unassigned_teams: list<array<string, mixed>>
 * } $groupAssignment */
/** @var list<string> $matchModes */

$tournamentId = (int) ($tournament['id'] ?? 0);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h4 m-0">Tournament detail</h1>
    <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm">Back to dashboard</a>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Settings</h2>
                <form method="post" action="/admin/tournament/update">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
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
                    <button type="submit" class="btn btn-primary w-100">Save tournament settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5">Groups</h2>
                <p class="text-muted small mb-2">Group names are auto-generated as A, B, C...</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($groups as $group): ?>
                        <span class="badge text-bg-secondary px-3 py-2"><?= htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5">Group assignment</h2>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">Total teams</div>
                            <div class="fw-semibold"><?= (int) $groupAssignment['total_teams'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">Groups</div>
                            <div class="fw-semibold"><?= (int) $groupAssignment['group_count'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 bg-light">
                            <div class="small text-muted">Unassigned</div>
                            <div class="fw-semibold"><?= (int) $groupAssignment['unassigned_count'] ?></div>
                        </div>
                    </div>
                </div>

                <form method="post" action="/admin/tournament/teams/assign-auto" onsubmit="return confirm('Automatically assign all teams and overwrite current assignments?');" class="mb-3">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="confirm_overwrite" value="1">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Automatically assign teams to groups</button>
                </form>

                <div class="d-flex flex-column gap-3">
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $groupId = (int) ($group['id'] ?? 0);
                        $groupName = (string) ($group['name'] ?? '');
                        $groupTeams = $groupAssignment['grouped_teams'][$groupId] ?? [];
                        $teamCount = (int) ($groupAssignment['teams_per_group'][$groupId] ?? 0);
                        ?>
                        <div class="border rounded p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Group <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="badge text-bg-secondary"><?= $teamCount ?> teams</span>
                            </div>
                            <?php if (count($groupTeams) === 0): ?>
                                <p class="text-muted small mb-0">No teams assigned.</p>
                            <?php endif; ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($groupTeams as $team): ?>
                                    <?php $teamId = (int) ($team['id'] ?? 0); ?>
                                    <form method="post" action="/admin/tournament/teams/assign" class="row g-2 align-items-center">
                                        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                        <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                        <div class="col-12 col-md-6">
                                            <span><?= htmlspecialchars((string) ($team['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <select name="group_id" class="form-select form-select-sm">
                                                <option value="">No group</option>
                                                <?php foreach ($groups as $optionGroup): ?>
                                                    <?php $optionId = (int) ($optionGroup['id'] ?? 0); ?>
                                                    <option value="<?= $optionId ?>" <?= $optionId === $groupId ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars((string) ($optionGroup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                                        </div>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border rounded p-2 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Unassigned teams</strong>
                        <span class="badge text-bg-secondary"><?= (int) $groupAssignment['unassigned_count'] ?></span>
                    </div>
                    <?php if (count($groupAssignment['unassigned_teams']) === 0): ?>
                        <p class="text-muted small mb-0">All teams are currently assigned.</p>
                    <?php endif; ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($groupAssignment['unassigned_teams'] as $team): ?>
                            <?php $teamId = (int) ($team['id'] ?? 0); ?>
                            <form method="post" action="/admin/tournament/teams/assign" class="row g-2 align-items-center">
                                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                <div class="col-12 col-md-6">
                                    <span><?= htmlspecialchars((string) ($team['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-12 col-md-4">
                                    <select name="group_id" class="form-select form-select-sm">
                                        <option value="" selected>No group</option>
                                        <?php foreach ($groups as $optionGroup): ?>
                                            <?php $optionId = (int) ($optionGroup['id'] ?? 0); ?>
                                            <option value="<?= $optionId ?>">
                                                <?= htmlspecialchars((string) ($optionGroup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Add team</h2>
                <form method="post" action="/admin/tournament/teams/create" class="mb-4">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <div class="mb-2">
                        <label for="team_name_new" class="form-label">Team name</label>
                        <input type="text" name="team_name" id="team_name_new" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label for="team_description_new" class="form-label">Description (optional)</label>
                        <textarea name="description" id="team_description_new" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Add team</button>
                </form>

                <h2 class="h6">Existing teams</h2>
                <?php if (count($teams) === 0): ?>
                    <p class="text-muted">No teams yet.</p>
                <?php endif; ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($teams as $team): ?>
                        <?php $teamId = (int) ($team['id'] ?? 0); ?>
                        <div class="border rounded p-2 bg-light-subtle">
                            <form method="post" action="/admin/tournament/teams/update">
                                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                <div class="mb-2">
                                    <label class="form-label">Team name</label>
                                    <input type="text" name="team_name" class="form-control" required maxlength="150" value="<?= htmlspecialchars((string) ($team['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="2" maxlength="1000"><?= htmlspecialchars((string) ($team['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            </form>
                            <div class="d-flex gap-2">
                                <form method="post" action="/admin/tournament/teams/delete" onsubmit="return confirm('Delete this team?');">
                                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                    <input type="hidden" name="confirm_delete" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
