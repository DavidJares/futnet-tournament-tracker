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
?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="text-muted small mb-2">
            Generates a full knockout structure. Non-power-of-two counts use byes for top seeds.
        </p>
        <p class="mb-2">
            <strong>Advancing teams:</strong> <?= $advancingTeamsCount ?>
        </p>
        <?php if ($hasExistingKnockoutMatches): ?>
            <div class="alert alert-warning py-2 mb-2 small" role="alert">
                Knockout matches already exist. Generation will replace knockout matches only.
            </div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($generateKnockoutMatchesActionUrl, ENT_QUOTES, 'UTF-8') ?>"<?= $generateConfirmMessage !== '' ? ' onsubmit="' . $generateConfirmMessage . '"' : '' ?>>
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="knockout">
            <?php if ($hasExistingKnockoutMatches): ?>
                <input type="hidden" name="confirm_regenerate" value="1">
            <?php endif; ?>
            <button type="submit" class="btn btn-outline-primary">Generate knockout stage</button>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="Knockout view">
        <a href="<?= htmlspecialchars($tableViewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $knockoutView === 'table' ? 'btn-primary' : 'btn-outline-primary' ?>">Table View</a>
        <a href="<?= htmlspecialchars($bracketViewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $knockoutView === 'bracket' ? 'btn-primary' : 'btn-outline-primary' ?>">Bracket View</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (count($knockoutMatches) === 0): ?>
            <p class="text-muted p-3 mb-0">No knockout matches generated yet.</p>
        <?php elseif ($knockoutView === 'bracket'): ?>
            <style>
                .kb-wrap {
                    overflow-x: auto;
                    padding: 1rem;
                }
                .kb-grid {
                    display: flex;
                    gap: 1rem;
                    align-items: flex-start;
                    min-width: max-content;
                }
                .kb-round {
                    width: 290px;
                    flex: 0 0 290px;
                }
                .kb-round-title {
                    font-size: 0.9rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    color: #6c757d;
                    margin-bottom: 0.5rem;
                }
                .kb-match-card {
                    border: 1px solid #dee2e6;
                    border-radius: 0.5rem;
                    background: #fff;
                    padding: 0.6rem 0.7rem;
                    margin-bottom: 0.8rem;
                }
                .kb-match-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 0.35rem;
                }
                .kb-match-label {
                    font-size: 0.85rem;
                    font-weight: 600;
                }
                .kb-team-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 0.5rem;
                    font-size: 0.93rem;
                    margin-bottom: 0.2rem;
                }
                .kb-team-name-win {
                    color: #3a7f5a;
                    font-weight: 600;
                }
                .kb-source {
                    color: #6c757d;
                    font-size: 0.78rem;
                }
                .kb-result {
                    margin-top: 0.35rem;
                    font-size: 0.85rem;
                    font-weight: 600;
                }
                .kb-meta {
                    margin-top: 0.4rem;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 0.45rem 0.6rem;
                    font-size: 0.78rem;
                    color: #6c757d;
                }
            </style>
            <div class="kb-wrap">
                <div class="kb-grid">
                    <?php foreach ($rounds as $roundName => $roundMatches): ?>
                        <div class="kb-round">
                            <div class="kb-round-title"><?= htmlspecialchars($roundName !== '' ? $roundName : 'Round', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php foreach ($roundMatches as $roundRow): ?>
                                <?php
                                $index = (int) ($roundRow['index'] ?? 0);
                                $match = is_array($roundRow['match'] ?? null) ? $roundRow['match'] : [];
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
                                $teamAId = (int) ($match['team_a_id'] ?? 0);
                                $teamBId = (int) ($match['team_b_id'] ?? 0);
                                $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                                $teamAWinClass = $winnerTeamId > 0 && $winnerTeamId === $teamAId ? 'kb-team-name-win' : '';
                                $teamBWinClass = $winnerTeamId > 0 && $winnerTeamId === $teamBId ? 'kb-team-name-win' : '';

                                $teamASource = trim((string) ($match['team_a_source'] ?? ''));
                                $teamBSource = trim((string) ($match['team_b_source'] ?? ''));
                                $teamASourceDisplay = ($teamASource !== '' && isset($sourceLabelByCode[$teamASource])) ? ('Winner of ' . $sourceLabelByCode[$teamASource]) : $teamASource;
                                $teamBSourceDisplay = ($teamBSource !== '' && isset($sourceLabelByCode[$teamBSource])) ? ('Winner of ' . $sourceLabelByCode[$teamBSource]) : $teamBSource;
                                $courtNumber = (int) ($match['court_number'] ?? 0);
                                $courtBadgeClass = $courtNumber > 0 ? ($courtBadgeClasses[($courtNumber - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                                $estimatedStartRaw = trim((string) ($match['planned_start'] ?? ''));
                                $estimatedStartDisplay = 'TBD';
                                if ($estimatedStartRaw !== '') {
                                    $estimatedDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $estimatedStartRaw);
                                    if ($estimatedDateTime instanceof \DateTimeImmutable) {
                                        $estimatedStartDisplay = $estimatedDateTime->format('H:i');
                                    }
                                }
                                $detailUrl = (string) ($match['detail_url'] ?? '');
                                ?>
                                <div class="kb-match-card<?= $detailUrl !== '' ? ' js-match-row' : '' ?>"<?= $detailUrl !== '' ? ' data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                    <div class="kb-match-header">
                                        <div class="kb-match-label"><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="kb-team-row">
                                        <span class="<?= htmlspecialchars($teamAWinClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($winnerTeamId > 0 && $winnerTeamId === $teamAId): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($teamASourceDisplay !== ''): ?>
                                        <div class="kb-source"><?= htmlspecialchars($teamASourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <div class="kb-team-row">
                                        <span class="<?= htmlspecialchars($teamBWinClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($winnerTeamId > 0 && $winnerTeamId === $teamBId): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($teamBSourceDisplay !== ''): ?>
                                        <div class="kb-source"><?= htmlspecialchars($teamBSourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if ($status === 'finished'): ?>
                                        <div class="kb-result">Result: <?= htmlspecialchars($setsSummaryA . ':' . $setsSummaryB, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <div class="kb-meta">
                                        <?php if ($courtNumber > 0): ?>
                                            <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $courtNumber ?></span>
                                        <?php else: ?>
                                            <span>Court TBD</span>
                                        <?php endif; ?>
                                        <span>Estimated start: <?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
                document.querySelectorAll('.js-match-row').forEach(function (row) {
                    row.style.cursor = 'pointer';
                    row.addEventListener('click', function () {
                        var href = row.getAttribute('data-href');
                        if (href) {
                            window.location.href = href;
                        }
                    });
                });
            </script>
        <?php else: ?>
            <style>
                .ko-team-winner {
                    font-weight: 600;
                    color: #3a7f5a;
                }
                .ko-source-label {
                    color: #6c757d;
                    font-size: 0.8rem;
                    line-height: 1.15;
                }
                .ko-result-detail {
                    color: #6c757d;
                    font-size: 0.8rem;
                    line-height: 1.15;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Match</th>
                        <th>Team A</th>
                        <th>Team B</th>
                        <th>Court</th>
                        <th>Estimated start</th>
                        <th>Result</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($knockoutMatches as $index => $match): ?>
                        <?php
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
                        $teamASource = trim((string) ($match['team_a_source'] ?? ''));
                        $teamBSource = trim((string) ($match['team_b_source'] ?? ''));
                        $teamASourceDisplay = $teamASource;
                        $teamBSourceDisplay = $teamBSource;
                        if ($teamASource !== '' && isset($sourceLabelByCode[$teamASource])) {
                            $teamASourceDisplay = 'Winner of ' . $sourceLabelByCode[$teamASource];
                        }
                        if ($teamBSource !== '' && isset($sourceLabelByCode[$teamBSource])) {
                            $teamBSourceDisplay = 'Winner of ' . $sourceLabelByCode[$teamBSource];
                        }
                        $teamAId = (int) ($match['team_a_id'] ?? 0);
                        $teamBId = (int) ($match['team_b_id'] ?? 0);
                        $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
                        $isWinnerA = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamAId;
                        $isWinnerB = $status === 'finished' && $winnerTeamId > 0 && $winnerTeamId === $teamBId;
                        $teamANameClass = $isWinnerA ? 'ko-team-winner' : '';
                        $teamBNameClass = $isWinnerB ? 'ko-team-winner' : '';
                        $courtNumber = (int) ($match['court_number'] ?? 0);
                        $courtBadgeClass = $courtNumber > 0 ? ($courtBadgeClasses[($courtNumber - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                        $estimatedStartRaw = trim((string) ($match['planned_start'] ?? ''));
                        $estimatedStartDisplay = 'TBD';
                        if ($estimatedStartRaw !== '') {
                            $estimatedDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $estimatedStartRaw);
                            if ($estimatedDateTime instanceof \DateTimeImmutable) {
                                $estimatedStartDisplay = $estimatedDateTime->format('H:i');
                            } else {
                                $estimatedStartDisplay = $estimatedStartRaw;
                            }
                        }
                        ?>
                        <?php $detailUrl = (string) ($match['detail_url'] ?? ''); ?>
                        <tr<?= $detailUrl !== '' ? ' class="js-match-row" data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <td><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php $teamAName = trim((string) ($match['team_a_name'] ?? '')); ?>
                                <?php if ($teamAName !== ''): ?>
                                    <span class="<?= htmlspecialchars($teamANameClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($teamAName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isWinnerA): ?>
                                        <span class="badge text-bg-light border text-secondary">W</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">TBD</span>
                                <?php endif; ?>
                                <?php if ($teamASource !== ''): ?>
                                    <div class="ko-source-label"><?= htmlspecialchars($teamASourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $teamBName = trim((string) ($match['team_b_name'] ?? '')); ?>
                                <?php if ($teamBName !== ''): ?>
                                    <span class="<?= htmlspecialchars($teamBNameClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($teamBName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isWinnerB): ?>
                                        <span class="badge text-bg-light border text-secondary">W</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">TBD</span>
                                <?php endif; ?>
                                <?php if ($teamBSource !== ''): ?>
                                    <div class="ko-source-label"><?= htmlspecialchars($teamBSourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($courtNumber > 0): ?>
                                    <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $courtNumber ?></span>
                                <?php else: ?>
                                    <span class="text-muted">TBD</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div><?= htmlspecialchars($resultSummary, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                                    <div class="ko-result-detail">(<?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
                document.querySelectorAll('.js-match-row').forEach(function (row) {
                    row.style.cursor = 'pointer';
                    row.addEventListener('click', function () {
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
