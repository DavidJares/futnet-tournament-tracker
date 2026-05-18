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

$matchViewData = static function (array $match) use ($courtBadgeClasses): array {
    $plannedStartRaw = (string) ($match['planned_start'] ?? '');
    $plannedStart = $plannedStartRaw;
    if ($plannedStartRaw !== '') {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $plannedStartRaw);
        if ($dateTime instanceof \DateTimeImmutable) {
            $plannedStart = $dateTime->format('H:i');
        }
    }

    $courtNumber = (int) ($match['court_number'] ?? 0);
    $courtBadgeClass = 'text-bg-secondary';
    if ($courtNumber > 0) {
        $courtBadgeClass = $courtBadgeClasses[($courtNumber - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary';
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

    return [
        'planned_start' => $plannedStart !== '' ? $plannedStart : '-',
        'court_number' => $courtNumber,
        'court_badge_class' => $courtBadgeClass,
        'status' => $status,
        'status_class' => $statusClass,
        'result_summary' => $resultSummary,
        'set_scores_summary' => $setScoresSummary,
        'is_winner_a' => $isWinnerA,
        'is_winner_b' => $isWinnerB,
        'detail_url' => (string) ($match['detail_url'] ?? ''),
    ];
};

$renderTeamName = static function (string $teamName, bool $isWinner): void {
    ?>
    <span class="bb-admin-match-team-name <?= $isWinner ? 'match-winner-name' : '' ?>">
        <?= htmlspecialchars($teamName !== '' ? $teamName : '-', ENT_QUOTES, 'UTF-8') ?>
    </span>
    <?php if ($isWinner): ?>
        <span class="badge text-bg-light border text-secondary match-winner-badge">W</span>
    <?php endif; ?>
    <?php
};

$renderStartAction = static function (array $match, string $status, int $tournamentId, int $selectedGroupFilter, int $selectedCourtFilter): void {
    if ($status !== 'scheduled') {
        ?>
        <span class="text-muted small">Open detail</span>
        <?php
        return;
    }
    ?>
    <form method="post" action="<?= htmlspecialchars((string) ($match['start_action_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="m-0 js-match-action">
        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
        <input type="hidden" name="group_id" value="<?= $selectedGroupFilter ?>">
        <input type="hidden" name="court" value="<?= $selectedCourtFilter ?>">
        <input type="hidden" name="return_to" value="matches">
        <button type="submit" class="btn btn-sm btn-outline-primary">Start</button>
    </form>
    <?php
};
?>
<div class="bb-workspace bb-stage-workspace">
    <header class="bb-workspace-header">
        <div>
            <div class="bb-page-kicker">Match control</div>
            <h2>Group Stage</h2>
            <p>Generate, schedule and manage group-stage matches.</p>
        </div>
    </header>

    <section class="bb-stage-action-card">
        <div class="bb-stage-action-copy">
            <span class="bb-settings-eyebrow">Generation</span>
            <h3>Generate Group-stage Matches</h3>
            <p>Creates round-robin matches in each group, then assigns courts, schedule order and planned start times.</p>
        </div>
        <div class="bb-stage-action-side">
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
                <button type="submit" class="btn btn-primary w-100">Generate group-stage matches</button>
            </form>
        </div>
    </section>

    <?php if ($groupMatchesTotalCount > 0): ?>
        <section class="bb-stage-toolbar">
            <form method="get" action="<?= htmlspecialchars($matchesFilterActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="bb-stage-filter-form">
                <input type="hidden" name="id" value="<?= $tournamentId ?>">
                <div>
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
                <div>
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
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= htmlspecialchars($matchesFilterActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </form>
        </section>
    <?php endif; ?>

    <section class="bb-stage-board">
        <div class="bb-group-card-header">
            <div>
                <span class="bb-settings-eyebrow">Matches</span>
                <h3>Match Overview</h3>
            </div>
            <span class="bb-status-pill"><?= count($groupMatches) ?> shown / <?= $groupMatchesTotalCount ?> total</span>
        </div>

        <?php if (count($groupMatches) === 0): ?>
            <div class="bb-empty-state">
                <?php if ($groupMatchesTotalCount > 0): ?>
                    No matches found for selected filters.
                <?php else: ?>
                    No group-stage matches generated yet. Use the generation action above to prepare the schedule.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bb-admin-match-table-wrap">
                <table class="table table-sm mb-0 align-middle bb-admin-match-table">
                    <colgroup>
                        <col class="bb-match-col-order">
                        <col class="bb-match-col-group">
                        <col class="bb-match-col-match">
                        <col class="bb-match-col-result">
                        <col class="bb-match-col-court">
                        <col class="bb-match-col-start">
                        <col class="bb-match-col-status">
                        <col class="bb-match-col-action">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Group</th>
                        <th>Match</th>
                        <th>Result</th>
                        <th>Court</th>
                        <th>Start</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groupMatches as $match): ?>
                        <?php
                        $view = $matchViewData($match);
                        $detailUrl = (string) $view['detail_url'];
                        $teamAName = (string) ($match['team_a_name'] ?? '-');
                        $teamBName = (string) ($match['team_b_name'] ?? '-');
                        ?>
                        <tr<?= $detailUrl !== '' ? ' class="js-match-row bb-admin-match-row" data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : ' class="bb-admin-match-row"' ?>>
                            <td><span class="bb-admin-match-order"><?= (int) ($match['schedule_order'] ?? 0) ?></span></td>
                            <td><?= htmlspecialchars((string) ($match['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="bb-admin-match-teams">
                                    <div><?php $renderTeamName($teamAName, (bool) $view['is_winner_a']); ?></div>
                                    <div><?php $renderTeamName($teamBName, (bool) $view['is_winner_b']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="bb-admin-match-result"><?= htmlspecialchars((string) $view['result_summary'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ((string) $view['set_scores_summary'] !== ''): ?>
                                    <div class="small text-muted">(<?= htmlspecialchars((string) $view['set_scores_summary'], ENT_QUOTES, 'UTF-8') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $view['court_number'] > 0): ?>
                                    <span class="badge <?= htmlspecialchars((string) $view['court_badge_class'], ENT_QUOTES, 'UTF-8') ?>">Court <?= (int) $view['court_number'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $view['planned_start'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= htmlspecialchars((string) $view['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $view['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-end">
                                <?php $renderStartAction($match, (string) $view['status'], $tournamentId, $selectedGroupFilter, $selectedCourtFilter); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bb-admin-match-cards">
                <?php foreach ($groupMatches as $match): ?>
                    <?php
                    $view = $matchViewData($match);
                    $detailUrl = (string) $view['detail_url'];
                    $teamAName = (string) ($match['team_a_name'] ?? '-');
                    $teamBName = (string) ($match['team_b_name'] ?? '-');
                    ?>
                    <article class="bb-admin-match-card<?= $detailUrl !== '' ? ' js-match-card' : '' ?>"<?= $detailUrl !== '' ? ' data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                        <div class="bb-admin-match-card-top">
                            <span class="bb-admin-match-order">#<?= (int) ($match['schedule_order'] ?? 0) ?></span>
                            <span><?= htmlspecialchars((string) ($match['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge <?= htmlspecialchars((string) $view['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $view['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="bb-admin-match-card-teams">
                            <div><?php $renderTeamName($teamAName, (bool) $view['is_winner_a']); ?></div>
                            <div><?php $renderTeamName($teamBName, (bool) $view['is_winner_b']); ?></div>
                        </div>
                        <div class="bb-admin-match-card-meta">
                            <div>
                                <span>Result</span>
                                <strong><?= htmlspecialchars((string) $view['result_summary'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ((string) $view['set_scores_summary'] !== ''): ?>
                                    <small>(<?= htmlspecialchars((string) $view['set_scores_summary'], ENT_QUOTES, 'UTF-8') ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span>Court</span>
                                <?php if ((int) $view['court_number'] > 0): ?>
                                    <strong>Court <?= (int) $view['court_number'] ?></strong>
                                <?php else: ?>
                                    <strong>-</strong>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span>Start</span>
                                <strong><?= htmlspecialchars((string) $view['planned_start'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        </div>
                        <div class="bb-admin-match-card-action">
                            <?php $renderStartAction($match, (string) $view['status'], $tournamentId, $selectedGroupFilter, $selectedCourtFilter); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<script>
    (function () {
        var openTarget = function (container, event) {
            if (event.target.closest('.js-match-action, a, button, input, select, textarea')) {
                return;
            }

            var href = container.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        };

        document.querySelectorAll('.js-match-row, .js-match-card').forEach(function (item) {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function (event) {
                openTarget(item, event);
            });
        });
    })();
</script>
