<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var list<array<string, mixed>> $groups */
/** @var array{
 *     total_teams: int,
 *     group_count: int,
 *     unassigned_count: int,
 *     teams_per_group: array<int, int>,
 *     grouped_teams: array<int, list<array<string, mixed>>>,
 *     unassigned_teams: list<array<string, mixed>>
 * } $groupAssignment */
/** @var string $assignTeamActionUrl */
/** @var string $autoAssignTeamsActionUrl */
/** @var array<int, list<array<string, int|string>>> $groupStandingsByGroup */

$tournamentId = (int) ($tournament['id'] ?? 0);
?>
<div class="card shadow-sm">
    <div class="card-body">
        <p class="text-muted small mb-2">Group names are auto-generated as A, B, C...</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($groups as $group): ?>
                <span class="badge text-bg-secondary"><?= htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>

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

        <form method="post" action="<?= htmlspecialchars($autoAssignTeamsActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Automatically assign all teams and overwrite current assignments?');" class="mb-3">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="confirm_overwrite" value="1">
            <input type="hidden" name="return_section" value="groups">
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
                    <?php $standingsRows = $groupStandingsByGroup[$groupId] ?? []; ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Team</th>
                                <th>MP</th>
                                <th>W</th>
                                <th>D</th>
                                <th>L</th>
                                <th>SF</th>
                                <th>SA</th>
                                <th>PF</th>
                                <th>PA</th>
                                <th>+/-</th>
                                <th>Pts</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($standingsRows) === 0): ?>
                                <tr>
                                    <td colspan="12" class="text-muted">No teams assigned.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($standingsRows as $row): ?>
                                <tr>
                                    <td><?= (int) ($row['position'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) ($row['played'] ?? 0) ?></td>
                                    <td><?= (int) ($row['wins'] ?? 0) ?></td>
                                    <td><?= (int) ($row['draws'] ?? 0) ?></td>
                                    <td><?= (int) ($row['losses'] ?? 0) ?></td>
                                    <td><?= (int) ($row['sets_for'] ?? 0) ?></td>
                                    <td><?= (int) ($row['sets_against'] ?? 0) ?></td>
                                    <td><?= (int) ($row['points_for'] ?? 0) ?></td>
                                    <td><?= (int) ($row['points_against'] ?? 0) ?></td>
                                    <td><?= (int) ($row['point_diff'] ?? 0) ?></td>
                                    <td><strong><?= (int) ($row['tournament_points'] ?? 0) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($groupTeams as $team): ?>
                            <?php $teamId = (int) ($team['id'] ?? 0); ?>
                            <form method="post" action="<?= htmlspecialchars($assignTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-center">
                                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                <input type="hidden" name="return_section" value="groups">
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
                    <form method="post" action="<?= htmlspecialchars($assignTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-center">
                        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                        <input type="hidden" name="team_id" value="<?= $teamId ?>">
                        <input type="hidden" name="return_section" value="groups">
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
