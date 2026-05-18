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
/** @var string $createTeamActionUrl */
/** @var string $updateTeamActionUrl */
/** @var string $deleteTeamActionUrl */
/** @var string $assignTeamActionUrl */
/** @var string $autoAssignTeamsActionUrl */
/** @var array<int, list<array<string, int|string>>> $groupStandingsByGroup */

$tournamentId = (int) ($tournament['id'] ?? 0);
$totalTeams = (int) $groupAssignment['total_teams'];
$groupCount = (int) $groupAssignment['group_count'];
$unassignedCount = (int) $groupAssignment['unassigned_count'];
$assignedCount = max(0, $totalTeams - $unassignedCount);

$renderAssignmentOptions = static function (array $groups, ?int $selectedGroupId): void {
    ?>
    <option value="" <?= $selectedGroupId === null ? 'selected' : '' ?>>No group</option>
    <?php foreach ($groups as $optionGroup): ?>
        <?php $optionId = (int) ($optionGroup['id'] ?? 0); ?>
        <option value="<?= $optionId ?>" <?= $selectedGroupId === $optionId ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) ($optionGroup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </option>
    <?php endforeach; ?>
    <?php
};

$renderTeamCard = static function (
    array $team,
    array $groups,
    ?int $selectedGroupId,
    int $tournamentId,
    string $assignTeamActionUrl,
    string $updateTeamActionUrl,
    string $deleteTeamActionUrl,
    callable $renderAssignmentOptions
): void {
    $teamId = (int) ($team['id'] ?? 0);
    $teamName = (string) ($team['team_name'] ?? '');
    $description = (string) ($team['description'] ?? '');
    $groupName = '';
    if ($selectedGroupId !== null) {
        foreach ($groups as $group) {
            if ((int) ($group['id'] ?? 0) === $selectedGroupId) {
                $groupName = (string) ($group['name'] ?? '');
                break;
            }
        }
    }
    $assignmentLabel = $selectedGroupId === null ? 'Unassigned' : ('Group ' . $groupName);
    $editPanelId = 'team-edit-panel-' . $teamId;
    ?>
    <article class="bb-team-item <?= $selectedGroupId === null ? 'bb-team-item-unassigned' : '' ?>">
        <div class="bb-team-item-header">
            <div class="bb-team-item-main">
                <strong title="<?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?>
                </strong>
                <?php if ($description !== ''): ?>
                    <span><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <div class="bb-team-item-meta">
                    <span class="bb-team-badge"><?= htmlspecialchars($assignmentLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="bb-team-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary bb-team-edit-toggle" data-team-edit-target="<?= htmlspecialchars($editPanelId, ENT_QUOTES, 'UTF-8') ?>" aria-controls="<?= htmlspecialchars($editPanelId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false">Edit</button>
                <form method="post" action="<?= htmlspecialchars($deleteTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Delete this team?');" class="bb-team-delete-form">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                    <input type="hidden" name="confirm_delete" value="1">
                    <input type="hidden" name="return_section" value="groups">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>

        <div id="<?= htmlspecialchars($editPanelId, ENT_QUOTES, 'UTF-8') ?>" class="bb-team-edit-panel" hidden>
            <div class="bb-team-edit-panel-header">
                <strong>Edit team</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary bb-team-edit-cancel" data-team-edit-target="<?= htmlspecialchars($editPanelId, ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
            </div>

            <form method="post" action="<?= htmlspecialchars($updateTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="bb-team-edit-form">
                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                <input type="hidden" name="return_section" value="groups">
                <div>
                    <label class="form-label">Team name</label>
                    <input type="text" name="team_name" class="form-control form-control-sm" required maxlength="150" value="<?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="1000"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save details</button>
            </form>

            <form method="post" action="<?= htmlspecialchars($assignTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="bb-team-assignment-form">
                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                <input type="hidden" name="return_section" value="groups">
                <div>
                    <label class="form-label">Group assignment</label>
                    <select name="group_id" class="form-select form-select-sm" aria-label="Assign <?= htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8') ?> to group">
                        <?php $renderAssignmentOptions($groups, $selectedGroupId); ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save group</button>
            </form>
        </div>
    </article>
    <?php
};
?>
<div class="bb-workspace bb-teams-workspace">
    <header class="bb-workspace-header">
        <div>
            <div class="bb-page-kicker">Preparation</div>
            <h2>Teams &amp; Groups</h2>
            <p>Manage participants and group assignments before match generation.</p>
        </div>
    </header>

    <section class="bb-metric-grid" aria-label="Team and group summary">
        <div class="bb-metric-card">
            <span>Total teams</span>
            <strong><?= $totalTeams ?></strong>
        </div>
        <div class="bb-metric-card">
            <span>Groups</span>
            <strong><?= $groupCount ?></strong>
        </div>
        <div class="bb-metric-card">
            <span>Assigned</span>
            <strong><?= $assignedCount ?></strong>
        </div>
        <div class="bb-metric-card <?= $unassignedCount > 0 ? 'bb-metric-card-warning' : '' ?>">
            <span>Unassigned</span>
            <strong><?= $unassignedCount ?></strong>
        </div>
    </section>

    <div class="bb-workspace-grid">
        <aside class="bb-workspace-rail">
            <section class="bb-action-card">
                <div class="bb-workspace-card-header">
                    <div>
                        <span class="bb-settings-eyebrow">Add participant</span>
                        <h3>Add Team</h3>
                    </div>
                </div>
                <form method="post" action="<?= htmlspecialchars($createTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="bb-stack-form">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="return_section" value="groups">
                    <div>
                        <label for="team_name_new" class="form-label">Team name</label>
                        <input type="text" name="team_name" id="team_name_new" class="form-control" required maxlength="150">
                    </div>
                    <div>
                        <label for="team_description_new" class="form-label">Description (optional)</label>
                        <textarea name="description" id="team_description_new" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add team</button>
                </form>
            </section>

            <section class="bb-action-card bb-action-card-accent">
                <div class="bb-workspace-card-header">
                    <div>
                        <span class="bb-settings-eyebrow">Group draw</span>
                        <h3>Balanced Assignment</h3>
                    </div>
                    <span class="bb-status-pill"><?= $totalTeams ?> teams</span>
                </div>
                <p class="bb-card-copy">Randomly distribute all teams across groups as evenly as possible. Existing assignments will be overwritten after confirmation.</p>
                <form method="post" action="<?= htmlspecialchars($autoAssignTeamsActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Automatically assign all teams and overwrite current assignments?');">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="confirm_overwrite" value="1">
                    <input type="hidden" name="return_section" value="groups">
                    <button type="submit" class="btn btn-outline-primary w-100">Automatically assign teams</button>
                </form>
            </section>
        </aside>

        <main class="bb-workspace-board">
            <section class="bb-group-card bb-group-card-unassigned">
                <div class="bb-group-card-header">
                    <div>
                        <span class="bb-settings-eyebrow">Needs attention</span>
                        <h3>Unassigned Teams</h3>
                    </div>
                    <span class="bb-status-pill"><?= $unassignedCount ?></span>
                </div>
                <?php if (count($groupAssignment['unassigned_teams']) === 0): ?>
                    <div class="bb-empty-state">All teams are currently assigned.</div>
                <?php else: ?>
                    <div class="bb-team-card-list">
                        <?php foreach ($groupAssignment['unassigned_teams'] as $team): ?>
                            <?php
                            $renderTeamCard(
                                $team,
                                $groups,
                                null,
                                $tournamentId,
                                $assignTeamActionUrl,
                                $updateTeamActionUrl,
                                $deleteTeamActionUrl,
                                $renderAssignmentOptions
                            );
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="bb-group-section">
                <div class="bb-board-section-heading">
                    <div>
                        <span class="bb-settings-eyebrow">Generated groups</span>
                        <h3>Group Cards</h3>
                    </div>
                    <div class="bb-group-chip-list" aria-label="Groups">
                        <?php foreach ($groups as $group): ?>
                            <span class="bb-group-chip"><?= htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bb-group-card-grid">
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $groupId = (int) ($group['id'] ?? 0);
                        $groupName = (string) ($group['name'] ?? '');
                        $groupTeams = $groupAssignment['grouped_teams'][$groupId] ?? [];
                        $teamCount = (int) ($groupAssignment['teams_per_group'][$groupId] ?? 0);
                        $standingsRows = $groupStandingsByGroup[$groupId] ?? [];
                        ?>
                        <article class="bb-group-card">
                            <div class="bb-group-card-header">
                                <div>
                                    <span class="bb-settings-eyebrow">Group</span>
                                    <h3><?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></h3>
                                </div>
                                <span class="bb-status-pill"><?= $teamCount ?> teams</span>
                            </div>

                            <?php if (count($groupTeams) === 0): ?>
                                <div class="bb-empty-state">No teams assigned to this group yet.</div>
                            <?php else: ?>
                                <div class="bb-team-card-list">
                                    <?php foreach ($groupTeams as $team): ?>
                                        <?php
                                        $renderTeamCard(
                                            $team,
                                            $groups,
                                            $groupId,
                                            $tournamentId,
                                            $assignTeamActionUrl,
                                            $updateTeamActionUrl,
                                            $deleteTeamActionUrl,
                                            $renderAssignmentOptions
                                        );
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <details class="bb-standings-details">
                                <summary>Standings snapshot</summary>
                                <div class="table-responsive mt-2">
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
                            </details>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
<script>
    (function () {
        var setPanelState = function (panel, open) {
            if (!panel) {
                return;
            }

            panel.hidden = !open;
            document.querySelectorAll('[data-team-edit-target="' + panel.id + '"]').forEach(function (button) {
                if (button.classList.contains('bb-team-edit-toggle')) {
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                    button.textContent = open ? 'Close' : 'Edit';
                }
            });
        };

        document.querySelectorAll('.bb-team-edit-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var panel = document.getElementById(button.getAttribute('data-team-edit-target') || '');
                setPanelState(panel, !!panel && panel.hidden);
            });
        });

        document.querySelectorAll('.bb-team-edit-cancel').forEach(function (button) {
            button.addEventListener('click', function () {
                var panel = document.getElementById(button.getAttribute('data-team-edit-target') || '');
                setPanelState(panel, false);
            });
        });
    })();
</script>
