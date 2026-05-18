<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var list<array<string, mixed>> $knockoutMatches */
/** @var bool $hasKnockoutMatches */
/** @var string $generateKnockoutMatchesActionUrl */

$tournamentId = (int) ($tournament['id'] ?? 0);
$advancingTeamsCount = (int) ($tournament['advancing_teams_count'] ?? 0);
$hasExistingKnockoutMatches = isset($hasKnockoutMatches) && $hasKnockoutMatches;
$generateConfirmMessage = $hasExistingKnockoutMatches
    ? "return confirm('Existing knockout matches will be replaced. Continue?');"
    : '';

$nextPowerOfTwo = static function (int $value): int {
    $power = 1;
    while ($power < max(1, $value)) {
        $power *= 2;
    }

    return $power;
};

$bracketSize = $advancingTeamsCount > 0 ? $nextPowerOfTwo($advancingTeamsCount) : 0;
$byeCount = $bracketSize > 0 ? max(0, $bracketSize - $advancingTeamsCount) : 0;
$matchCount = count($knockoutMatches);
$pendingMatchCount = 0;
$scheduledMatchCount = 0;
$inProgressMatchCount = 0;
$finishedMatchCount = 0;
foreach ($knockoutMatches as $knockoutMatch) {
    $status = (string) ($knockoutMatch['status'] ?? 'pending');
    if ($status === 'scheduled') {
        $scheduledMatchCount++;
    } elseif ($status === 'in_progress') {
        $inProgressMatchCount++;
    } elseif ($status === 'finished') {
        $finishedMatchCount++;
    } else {
        $pendingMatchCount++;
    }
}

$groupStageMatchCount = isset($groupMatchesTotalCount) ? (int) $groupMatchesTotalCount : (isset($groupMatches) && is_array($groupMatches) ? count($groupMatches) : 0);
$unfinishedGroupMatchCount = 0;
if (isset($groupMatches) && is_array($groupMatches)) {
    foreach ($groupMatches as $groupMatch) {
        if ((string) ($groupMatch['status'] ?? '') !== 'finished') {
            $unfinishedGroupMatchCount++;
        }
    }
}

$matchLabelsByIndex = [];
$sourceLabelByCode = [];
$courtBadgeClasses = [
    'text-bg-primary',
    'text-bg-success',
    'text-bg-info',
    'text-bg-warning',
    'text-bg-danger',
    'text-bg-secondary',
    'text-bg-dark',
];
$roundIndex = 0;
$currentRoundName = '';
$matchNumberInRound = 0;
$rounds = [];
foreach ($knockoutMatches as $index => $knockoutMatch) {
    $roundName = trim((string) ($knockoutMatch['round_name'] ?? ''));
    if ($roundName !== $currentRoundName) {
        $currentRoundName = $roundName;
        $roundIndex++;
        $matchNumberInRound = 0;
    }

    $matchNumberInRound++;
    $bracketPosition = (int) ($knockoutMatch['bracket_position'] ?? 0);
    if (strcasecmp($roundName, 'Final') === 0) {
        $matchLabel = 'Final';
    } else {
        $matchLabel = trim($roundName . ' ' . $bracketPosition);
    }

    $matchLabelsByIndex[$index] = $matchLabel !== '' ? $matchLabel : ('Match ' . $bracketPosition);
    $sourceLabelByCode['winner:r' . $roundIndex . ':m' . $matchNumberInRound] = $matchLabelsByIndex[$index];

    if (!isset($rounds[$roundName])) {
        $rounds[$roundName] = [];
    }
    $rounds[$roundName][] = [
        'index' => $index,
        'match' => $knockoutMatch,
    ];
}

foreach ($rounds as &$roundMatches) {
    usort(
        $roundMatches,
        static function (array $a, array $b): int {
            $positionA = (int) (($a['match']['bracket_position'] ?? 0));
            $positionB = (int) (($b['match']['bracket_position'] ?? 0));
            if ($positionA !== $positionB) {
                return $positionA <=> $positionB;
            }

            return (int) (($a['match']['id'] ?? 0)) <=> (int) (($b['match']['id'] ?? 0));
        }
    );
}
unset($roundMatches);

$knockoutView = trim((string) ($_GET['knockout_view'] ?? 'table'));
if (!in_array($knockoutView, ['table', 'bracket'], true)) {
    $knockoutView = 'table';
}

$currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$currentQueryString = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
$currentQuery = [];
if ($currentQueryString !== '') {
    parse_str($currentQueryString, $currentQuery);
}
$tableViewQuery = $currentQuery;
$tableViewQuery['knockout_view'] = 'table';
$bracketViewQuery = $currentQuery;
$bracketViewQuery['knockout_view'] = 'bracket';
$tableViewUrl = $currentPath . '?' . http_build_query($tableViewQuery);
$bracketViewUrl = $currentPath . '?' . http_build_query($bracketViewQuery);

$statusClassFor = static function (string $status): string {
    return match ($status) {
        'scheduled' => 'text-bg-primary',
        'in_progress' => 'text-bg-warning',
        'finished' => 'text-bg-success',
        default => 'text-bg-secondary',
    };
};

$statusLabelFor = static fn (string $status): string => ucwords(str_replace('_', ' ', $status !== '' ? $status : 'pending'));

$courtBadgeClassFor = static function (int $courtNumber) use ($courtBadgeClasses): string {
    if ($courtNumber <= 0) {
        return 'text-bg-secondary';
    }

    return $courtBadgeClasses[($courtNumber - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary';
};

$estimatedStartFor = static function (array $match): string {
    $estimatedStartRaw = trim((string) ($match['planned_start'] ?? ''));
    if ($estimatedStartRaw === '') {
        return 'TBD';
    }

    $estimatedDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $estimatedStartRaw);
    if ($estimatedDateTime instanceof \DateTimeImmutable) {
        return $estimatedDateTime->format('H:i');
    }

    return $estimatedStartRaw;
};

$sourceLabelFor = static function (string $source) use ($sourceLabelByCode): string {
    $source = trim($source);
    if ($source === '') {
        return '';
    }

    if (isset($sourceLabelByCode[$source])) {
        return 'Winner of ' . $sourceLabelByCode[$source];
    }

    if (strcasecmp($source, 'bye') === 0) {
        return 'BYE';
    }

    if (preg_match('/^winner:r(\d+):m(\d+)$/', $source, $matches) === 1) {
        return 'Winner of Match ' . (string) ((int) ($matches[2] ?? 0));
    }

    return 'Pending qualifier';
};

$teamViewData = static function (array $match, string $side) use ($sourceLabelFor): array {
    $teamKey = $side === 'a' ? 'team_a_name' : 'team_b_name';
    $sourceKey = $side === 'a' ? 'team_a_source' : 'team_b_source';
    $teamName = trim((string) ($match[$teamKey] ?? ''));
    $sourceLabel = $sourceLabelFor((string) ($match[$sourceKey] ?? ''));

    return [
        'name' => $teamName !== '' ? $teamName : 'TBD',
        'source' => $sourceLabel,
        'is_pending' => $teamName === '',
    ];
};

$renderTeamRow = static function (array $team, bool $isWinner, string $className = 'bb-ko-team-row'): void {
    ?>
    <div class="<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8') ?><?= $isWinner ? ' is-winner' : '' ?><?= !empty($team['is_pending']) ? ' is-pending' : '' ?>">
        <span class="bb-ko-team-name"><?= htmlspecialchars((string) ($team['name'] ?? 'TBD'), ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($isWinner): ?>
            <span class="bb-winner-badge">W</span>
        <?php endif; ?>
    </div>
    <?php if ((string) ($team['source'] ?? '') !== ''): ?>
        <div class="bb-bracket-source-label"><?= htmlspecialchars((string) $team['source'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php
};
?>

<section class="bb-knockout-shell">
    <header class="bb-workspace-header bb-knockout-header">
        <div>
            <span class="bb-section-kicker">Elimination bracket</span>
            <h2>Knockout</h2>
            <p>Generate, review and manage elimination bracket.</p>
        </div>
        <div class="bb-knockout-meta">
            <span><?= $advancingTeamsCount ?> advancing</span>
            <span><?= $bracketSize > 0 ? $bracketSize : '-' ?> bracket</span>
            <span><?= $byeCount > 0 ? ($byeCount . ' BYE' . ($byeCount === 1 ? '' : 's')) : 'No BYEs' ?></span>
        </div>
    </header>

    <section class="bb-knockout-toolbar">
        <div class="bb-knockout-action-copy">
            <span class="bb-section-kicker">Generate</span>
            <h3>Knockout Stage</h3>
            <p>Build the elimination bracket from advancing teams. Non-power-of-two fields use BYEs for top seeds.</p>

            <div class="bb-knockout-alerts">
                <?php if ($hasExistingKnockoutMatches): ?>
                    <div class="alert alert-warning py-2 mb-0 small" role="alert">
                        Knockout matches already exist. Generation will replace knockout matches only.
                    </div>
                <?php endif; ?>
                <?php if ($groupStageMatchCount <= 0): ?>
                    <div class="alert alert-warning py-2 mb-0 small" role="alert">
                        Generate and finish group-stage matches before creating the knockout bracket.
                    </div>
                <?php elseif ($unfinishedGroupMatchCount > 0): ?>
                    <div class="alert alert-warning py-2 mb-0 small" role="alert">
                        Group stage must be finished before generation. <?= $unfinishedGroupMatchCount ?> match<?= $unfinishedGroupMatchCount === 1 ? '' : 'es' ?> still need a final result.
                    </div>
                <?php endif; ?>
                <?php if ($advancingTeamsCount <= 1): ?>
                    <div class="alert alert-warning py-2 mb-0 small" role="alert">
                        Knockout generation requires at least 2 advancing teams.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bb-knockout-action-panel">
            <div class="bb-knockout-stats">
                <div>
                    <span>Matches</span>
                    <strong><?= $matchCount ?></strong>
                </div>
                <div>
                    <span>Finished</span>
                    <strong><?= $finishedMatchCount ?></strong>
                </div>
                <div>
                    <span>Pending</span>
                    <strong><?= $pendingMatchCount ?></strong>
                </div>
            </div>
            <form method="post" action="<?= htmlspecialchars($generateKnockoutMatchesActionUrl, ENT_QUOTES, 'UTF-8') ?>"<?= $generateConfirmMessage !== '' ? ' onsubmit="' . $generateConfirmMessage . '"' : '' ?>>
                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                <input type="hidden" name="return_section" value="knockout">
                <?php if ($hasExistingKnockoutMatches): ?>
                    <input type="hidden" name="confirm_regenerate" value="1">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100">Generate knockout stage</button>
            </form>
        </div>
    </section>

    <div class="bb-knockout-viewbar">
        <div>
            <span class="bb-section-kicker">Workspace</span>
            <strong><?= $knockoutView === 'bracket' ? 'Bracket View' : 'Table View' ?></strong>
        </div>
        <div class="bb-view-switcher" role="group" aria-label="Knockout view">
            <a href="<?= htmlspecialchars($tableViewUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $knockoutView === 'table' ? 'active' : '' ?>">Table View</a>
            <a href="<?= htmlspecialchars($bracketViewUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $knockoutView === 'bracket' ? 'active' : '' ?>">Bracket View</a>
        </div>
    </div>

    <section class="bb-knockout-board">
        <?php if ($matchCount === 0): ?>
            <div class="bb-empty-state">
                No knockout matches generated yet. Generate the knockout stage after group-stage results are complete.
            </div>
        <?php elseif ($knockoutView === 'bracket'): ?>
            <div class="bb-bracket-hint">Swipe horizontally to view bracket</div>
            <div class="bb-bracket-board" aria-label="Knockout bracket">
                <div class="bb-bracket-grid">
                    <?php foreach ($rounds as $roundName => $roundMatches): ?>
                        <section class="bb-bracket-round">
                            <div class="bb-bracket-round-title">
                                <span><?= htmlspecialchars($roundName !== '' ? $roundName : 'Round', ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= count($roundMatches) ?></strong>
                            </div>
                            <?php foreach ($roundMatches as $roundRow): ?>
                                <?php
                                $index = (int) ($roundRow['index'] ?? 0);
                                $match = is_array($roundRow['match'] ?? null) ? $roundRow['match'] : [];
                                $status = (string) ($match['status'] ?? 'pending');
                                $statusClass = $statusClassFor($status);
                                $statusLabel = $statusLabelFor($status);
                                $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                                $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                                $setScoresSummary = trim((string) ($match['set_scores_summary'] ?? ''));
                                $teamAId = (int) ($match['team_a_id'] ?? 0);
                                $teamBId = (int) ($match['team_b_id'] ?? 0);
                                $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                                $isWinnerA = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamAId;
                                $isWinnerB = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamBId;
                                $teamA = $teamViewData($match, 'a');
                                $teamB = $teamViewData($match, 'b');
                                $courtNumber = (int) ($match['court_number'] ?? 0);
                                $courtBadgeClass = $courtBadgeClassFor($courtNumber);
                                $estimatedStartDisplay = $estimatedStartFor($match);
                                $detailUrl = (string) ($match['detail_url'] ?? '');
                                ?>
                                <article class="bb-bracket-match-card bb-bracket-match-<?= htmlspecialchars(str_replace('_', '-', $status), ENT_QUOTES, 'UTF-8') ?><?= $detailUrl !== '' ? ' js-match-row' : '' ?>"<?= $detailUrl !== '' ? ' data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                    <div class="bb-bracket-match-header">
                                        <div>
                                            <span><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <strong><?= htmlspecialchars($roundName !== '' ? $roundName : 'Round', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <div class="bb-bracket-team-list">
                                        <?php $renderTeamRow($teamA, $isWinnerA, 'bb-bracket-team-row'); ?>
                                        <?php $renderTeamRow($teamB, $isWinnerB, 'bb-bracket-team-row'); ?>
                                    </div>

                                    <div class="bb-bracket-match-footer">
                                        <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= $courtNumber > 0 ? ('Court ' . $courtNumber) : 'Court TBD' ?></span>
                                        <span><?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <?php if ($status === 'finished'): ?>
                                        <div class="bb-bracket-result">
                                            <strong><?= htmlspecialchars($setsSummaryA . ':' . $setsSummaryB, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if ($setScoresSummary !== ''): ?>
                                                <span><?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bb-knockout-table-wrap">
                <table class="table table-sm align-middle mb-0 bb-knockout-table">
                    <colgroup>
                        <col class="bb-ko-col-match">
                        <col class="bb-ko-col-team">
                        <col class="bb-ko-col-team">
                        <col class="bb-ko-col-court">
                        <col class="bb-ko-col-start">
                        <col class="bb-ko-col-result">
                        <col class="bb-ko-col-status">
                        <col class="bb-ko-col-action">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Match / Round</th>
                        <th>Team A</th>
                        <th>Team B</th>
                        <th>Court</th>
                        <th>Start</th>
                        <th>Result</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($knockoutMatches as $index => $match): ?>
                        <?php
                        $status = (string) ($match['status'] ?? 'pending');
                        $statusClass = $statusClassFor($status);
                        $statusLabel = $statusLabelFor($status);
                        $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                        $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                        $resultSummary = $status === 'finished' ? ($setsSummaryA . ':' . $setsSummaryB) : '-';
                        $setScoresSummary = trim((string) ($match['set_scores_summary'] ?? ''));
                        $teamAId = (int) ($match['team_a_id'] ?? 0);
                        $teamBId = (int) ($match['team_b_id'] ?? 0);
                        $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                        $isWinnerA = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamAId;
                        $isWinnerB = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamBId;
                        $teamA = $teamViewData($match, 'a');
                        $teamB = $teamViewData($match, 'b');
                        $courtNumber = (int) ($match['court_number'] ?? 0);
                        $courtBadgeClass = $courtBadgeClassFor($courtNumber);
                        $estimatedStartDisplay = $estimatedStartFor($match);
                        $detailUrl = (string) ($match['detail_url'] ?? '');
                        $roundName = trim((string) ($match['round_name'] ?? ''));
                        ?>
                        <tr<?= $detailUrl !== '' ? ' class="js-match-row" data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <td>
                                <div class="bb-ko-match-cell">
                                    <strong><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($roundName !== '' ? $roundName : 'Round', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?php $renderTeamRow($teamA, $isWinnerA); ?></td>
                            <td><?php $renderTeamRow($teamB, $isWinnerB); ?></td>
                            <td>
                                <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= $courtNumber > 0 ? ('Court ' . $courtNumber) : 'TBD' ?></span>
                            </td>
                            <td><?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="bb-ko-result">
                                    <strong><?= htmlspecialchars($resultSummary, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                                        <span><?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <?php if ($detailUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Open</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bb-ko-mobile-cards">
                <?php foreach ($knockoutMatches as $index => $match): ?>
                    <?php
                    $status = (string) ($match['status'] ?? 'pending');
                    $statusClass = $statusClassFor($status);
                    $statusLabel = $statusLabelFor($status);
                    $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                    $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                    $setScoresSummary = trim((string) ($match['set_scores_summary'] ?? ''));
                    $teamAId = (int) ($match['team_a_id'] ?? 0);
                    $teamBId = (int) ($match['team_b_id'] ?? 0);
                    $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                    $isWinnerA = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamAId;
                    $isWinnerB = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamBId;
                    $teamA = $teamViewData($match, 'a');
                    $teamB = $teamViewData($match, 'b');
                    $courtNumber = (int) ($match['court_number'] ?? 0);
                    $courtBadgeClass = $courtBadgeClassFor($courtNumber);
                    $estimatedStartDisplay = $estimatedStartFor($match);
                    $detailUrl = (string) ($match['detail_url'] ?? '');
                    ?>
                    <article class="bb-admin-match-card<?= $detailUrl !== '' ? ' js-match-row' : '' ?>"<?= $detailUrl !== '' ? ' data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                        <div class="bb-admin-match-card-top">
                            <strong><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="bb-bracket-team-list">
                            <?php $renderTeamRow($teamA, $isWinnerA, 'bb-bracket-team-row'); ?>
                            <?php $renderTeamRow($teamB, $isWinnerB, 'bb-bracket-team-row'); ?>
                        </div>
                        <div class="bb-admin-match-card-meta">
                            <div><span>Court</span><strong><span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= $courtNumber > 0 ? ('Court ' . $courtNumber) : 'TBD' ?></span></strong></div>
                            <div><span>Start</span><strong><?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <div><span>Result</span><strong><?= htmlspecialchars($status === 'finished' ? ($setsSummaryA . ':' . $setsSummaryB) : '-', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        </div>
                        <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                            <div class="bb-ko-result mt-2"><span><?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <?php endif; ?>
                        <?php if ($detailUrl !== ''): ?>
                            <div class="bb-admin-match-card-action">
                                <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Open match</a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<script>
    document.querySelectorAll('.js-match-row').forEach(function (row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }

            var href = row.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });
</script>
