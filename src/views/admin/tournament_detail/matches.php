<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var array{
 *     unassigned_count: int
 * } $groupAssignment */
/** @var bool $hasGroupMatches */
/** @var string $generateGroupMatchesActionUrl */
/** @var string $matchesFilterActionUrl */
/** @var list<array<string, mixed>> $groupMatches */
/** @var int $groupMatchesTotalCount */
/** @var array<int, string> $groupFilterOptions */
/** @var list<int> $courtFilterOptions */
/** @var int $selectedGroupFilter */
/** @var int $selectedCourtFilter */

$tournamentId = (int) ($tournament['id'] ?? 0);
$hasUnassignedTeams = (int) ($groupAssignment['unassigned_count'] ?? 0) > 0;
$hasExistingGroupMatches = isset($hasGroupMatches) && $hasGroupMatches;
$generateConfirmMessageParts = [];
if ($hasUnassignedTeams) {
    $generateConfirmMessageParts[] = 'Some teams are unassigned and will be skipped.';
}
if ($hasExistingGroupMatches) {
    $generateConfirmMessageParts[] = 'Existing group-stage matches will be replaced.';
}
$generateConfirmMessage = implode(' ', $generateConfirmMessageParts);
$generateFormOnSubmit = $generateConfirmMessage !== '' ? "return confirm('" . htmlspecialchars($generateConfirmMessage . ' Continue?', ENT_QUOTES, 'UTF-8') . "');" : '';
$courtBadgeClasses = [
    'text-bg-primary',
    'text-bg-success',
    'text-bg-info',
    'text-bg-warning',
    'text-bg-danger',
    'text-bg-secondary',
    'text-bg-dark',
];
?>
<style>
    .match-winner-name {
        font-weight: 600;
        color: #3a7f5a;
    }

    .match-winner-badge {
        font-size: 0.65rem;
        font-weight: 500;
        line-height: 1;
    }
</style>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="text-muted small mb-2">
            Generates round-robin matches in each group, then assigns courts, schedule order, and planned start.
        </p>
        <?php if ($hasUnassignedTeams): ?>
            <div class="alert alert-warning py-2 mb-2 small" role="alert">
                There are unassigned teams. Generation will include only assigned teams.
            </div>
        <?php endif; ?>
        <?php if ($hasExistingGroupMatches): ?>
            <div class="alert alert-warning py-2 mb-2 small" role="alert">
                Group-stage matches already exist. Generation will replace existing group-stage matches.
            </div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($generateGroupMatchesActionUrl, ENT_QUOTES, 'UTF-8') ?>"<?= $generateFormOnSubmit !== '' ? ' onsubmit="' . $generateFormOnSubmit . '"' : '' ?>>
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="matches">
            <?php if ($hasUnassignedTeams): ?>
                <input type="hidden" name="confirm_unassigned" value="1">
            <?php endif; ?>
            <?php if ($hasExistingGroupMatches): ?>
                <input type="hidden" name="confirm_regenerate" value="1">
            <?php endif; ?>
            <button type="submit" class="btn btn-outline-primary">Generate group-stage matches</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if ($groupMatchesTotalCount > 0): ?>
            <div class="p-3 border-bottom">
                <form method="get" action="<?= htmlspecialchars($matchesFilterActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label for="group_id_filter" class="form-label mb-1">Group</label>
                        <select class="form-select form-select-sm" id="group_id_filter" name="group_id">
                            <option value="0">All groups</option>
                            <?php foreach ($groupFilterOptions as $groupId => $groupName): ?>
                                <option value="<?= (int) $groupId ?>" <?= $selectedGroupFilter === (int) $groupId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="court_filter" class="form-label mb-1">Court</label>
                        <select class="form-select form-select-sm" id="court_filter" name="court">
                            <option value="0">All courts</option>
                            <?php foreach ($courtFilterOptions as $court): ?>
                                <option value="<?= $court ?>" <?= $selectedCourtFilter === $court ? 'selected' : '' ?>>
                                    Court <?= $court ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-6 col-md-1">
                        <a href="<?= htmlspecialchars($matchesFilterActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        <?php if (count($groupMatches) === 0): ?>
            <?php if ($groupMatchesTotalCount > 0): ?>
                <p class="text-muted p-3 mb-0">No matches found for selected filters.</p>
            <?php else: ?>
                <p class="text-muted p-3 mb-0">No group-stage matches generated yet.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Group</th>
                        <th>Team A</th>
                        <th>Team B</th>
                        <th>Result</th>
                        <th>Court</th>
                        <th>Planned start</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groupMatches as $match): ?>
                        <?php
                        $plannedStartRaw = (string) ($match['planned_start'] ?? '');
                        $plannedStart = $plannedStartRaw;
                        $courtNumber = (int) ($match['court_number'] ?? 0);
                        $badgeClass = 'text-bg-secondary';
                        if ($courtNumber > 0) {
                            $badgeClass = $courtBadgeClasses[($courtNumber - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary';
                        }
                        if ($plannedStartRaw !== '') {
                            $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $plannedStartRaw);
                            if ($dateTime instanceof \DateTimeImmutable) {
                                $plannedStart = $dateTime->format('H:i');
                            }
                        }
                        $status = (string) ($match['status'] ?? 'pending');
                        $statusClass = 'text-bg-secondary';
                        if ($status === 'scheduled') {
                            $statusClass = 'text-bg-primary';
                        } elseif ($status === 'in_progress') {
                            $statusClass = 'text-bg-warning';
                        } elseif ($status === 'finished') {
                            $statusClass = 'text-bg-success';
                        }
                        $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                        $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                        $resultSummary = $status === 'finished' ? ($setsSummaryA . ':' . $setsSummaryB) : '-';
                        $setScoresSummary = trim((string) ($match['set_scores_summary'] ?? ''));
                        $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                        $isDrawResult = $status === 'finished' && $setsSummaryA === $setsSummaryB;
                        $isWinnerA = $status === 'finished' && !$isDrawResult && $winnerTeamId > 0 && $winnerTeamId === (int) ($match['team_a_id'] ?? 0);
                        $isWinnerB = $status === 'finished' && !$isDrawResult && $winnerTeamId > 0 && $winnerTeamId === (int) ($match['team_b_id'] ?? 0);
                        $teamANameClass = $isWinnerA ? 'match-winner-name' : '';
                        $teamBNameClass = $isWinnerB ? 'match-winner-name' : '';
                        ?>
                        <?php $detailUrl = (string) ($match['detail_url'] ?? ''); ?>
                        <tr<?= $detailUrl !== '' ? ' class="js-match-row" data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <td><?= (int) ($match['schedule_order'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($match['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="<?= htmlspecialchars($teamANameClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isWinnerA): ?>
                                        <span class="badge text-bg-light border text-secondary match-winner-badge">W</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    <span class="<?= htmlspecialchars($teamBNameClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isWinnerB): ?>
                                        <span class="badge text-bg-light border text-secondary match-winner-badge">W</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($resultSummary, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                                    <div class="small text-muted">(<?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($courtNumber > 0): ?>
                                    <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $courtNumber ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($plannedStart !== '' ? $plannedStart : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <?php if ($status === 'scheduled'): ?>
                                    <form method="post" action="<?= htmlspecialchars((string) ($match['start_action_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="m-0 js-match-action">
                                        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                        <input type="hidden" name="group_id" value="<?= $selectedGroupFilter ?>">
                                        <input type="hidden" name="court" value="<?= $selectedCourtFilter ?>">
                                        <input type="hidden" name="return_to" value="matches">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Start</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
                document.querySelectorAll('.js-match-row').forEach(function (row) {
                    row.style.cursor = 'pointer';
                    row.addEventListener('click', function (event) {
                        if (event.target.closest('.js-match-action')) {
                            return;
                        }
                        var href = row.getAttribute('data-href');
                        if (href) {
                            window.location.href = href;
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</div>
