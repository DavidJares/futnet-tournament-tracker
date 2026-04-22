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
$roundIndex = 0;
$currentRoundName = '';
$matchNumberInRound = 0;
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
}
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

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (count($knockoutMatches) === 0): ?>
            <p class="text-muted p-3 mb-0">No knockout matches generated yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Match</th>
                        <th>Team A</th>
                        <th>Team B</th>
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
                        ?>
                        <?php $detailUrl = (string) ($match['detail_url'] ?? ''); ?>
                        <tr<?= $detailUrl !== '' ? ' class="js-match-row" data-href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <td><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($teamASource !== ''): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($teamASourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($teamBSource !== ''): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($teamBSourceDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($resultSummary, ENT_QUOTES, 'UTF-8') ?></td>
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
