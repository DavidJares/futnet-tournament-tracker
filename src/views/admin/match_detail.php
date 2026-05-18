<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var array<string, mixed> $match */
/** @var list<array{set_number: int, score_a: int, score_b: int}> $matchSets */
/** @var array<string, int> $filters */
/** @var string $backToMatchesUrl */
/** @var string $scoreActionUrl */
/** @var string|null $startActionUrl */
/** @var string|null $resetActionUrl */
/** @var string|null $matchStage */
/** @var bool|null $requiresDependentResetConfirmation */

$tournamentId = (int) ($tournament['id'] ?? 0);
$matchStage = is_string($matchStage ?? null) ? $matchStage : 'group';
$isKnockoutStage = $matchStage === 'knockout';
$requiresDependentResetConfirmation = (bool) ($requiresDependentResetConfirmation ?? false);
$status = (string) ($match['status'] ?? 'pending');
$statusLabel = ucwords(str_replace('_', ' ', $status));
$matchMode = (string) ($match['match_mode'] ?? ($tournament['group_stage_mode'] ?? ($tournament['match_mode'] ?? '')));
$matchModeLabel = match ($matchMode) {
    'fixed_2_sets' => 'Fixed 2 sets',
    'best_of_3' => 'Best of 3',
    default => $matchMode !== '' ? ucwords(str_replace('_', ' ', $matchMode)) : 'Match mode',
};
$statusClass = 'text-bg-secondary';
if ($status === 'scheduled') {
    $statusClass = 'text-bg-primary';
} elseif ($status === 'in_progress') {
    $statusClass = 'text-bg-warning';
} elseif ($status === 'finished') {
    $statusClass = 'text-bg-success';
}

$plannedStartDisplay = $isKnockoutStage ? 'TBD' : '-';
$plannedStartRaw = (string) ($match['planned_start'] ?? '');
if ($plannedStartRaw !== '') {
    $plannedStartDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $plannedStartRaw);
    if ($plannedStartDate instanceof \DateTimeImmutable) {
        $plannedStartDisplay = $plannedStartDate->format('M j, H:i');
    }
}

$teamAName = (string) ($match['team_a_name'] ?? 'Team A');
$teamBName = (string) ($match['team_b_name'] ?? 'Team B');
$teamAId = (int) ($match['team_a_id'] ?? 0);
$teamBId = (int) ($match['team_b_id'] ?? 0);
$winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
$scoreEntryAllowed = in_array($status, ['scheduled', 'in_progress', 'finished'], true);
$maxSetNumber = $matchMode === 'fixed_2_sets' ? 2 : 3;
$setValues = [];
$setWinsA = 0;
$setWinsB = 0;
$setSummaryParts = [];
foreach ($matchSets as $set) {
    $setNumber = (int) ($set['set_number'] ?? 0);
    if ($setNumber <= 0) {
        continue;
    }

    $scoreA = (int) ($set['score_a'] ?? 0);
    $scoreB = (int) ($set['score_b'] ?? 0);
    $setValues[$setNumber] = [
        'score_a' => $scoreA,
        'score_b' => $scoreB,
    ];
    $setSummaryParts[] = $scoreA . ':' . $scoreB;
    if ($scoreA > $scoreB) {
        $setWinsA++;
    } elseif ($scoreB > $scoreA) {
        $setWinsB++;
    }
}

$setsSummaryA = (int) ($match['sets_summary_a'] ?? $setWinsA);
$setsSummaryB = (int) ($match['sets_summary_b'] ?? $setWinsB);

if ($winnerTeamId <= 0 && $status === 'finished') {
    if ($setsSummaryA > $setsSummaryB) {
        $winnerTeamId = $teamAId;
    } elseif ($setsSummaryB > $setsSummaryA) {
        $winnerTeamId = $teamBId;
    }
}
$teamAWon = $winnerTeamId > 0 && $teamAId > 0 && $winnerTeamId === $teamAId;
$teamBWon = $winnerTeamId > 0 && $teamBId > 0 && $winnerTeamId === $teamBId;
$resultDisplay = $status === 'finished' ? ($setsSummaryA . ' : ' . $setsSummaryB) : 'vs';
$setSummaryDisplay = $setSummaryParts !== [] ? implode(' / ', $setSummaryParts) : 'No set scores yet';

$contextLabel = $isKnockoutStage ? 'Round' : 'Group';
$contextValue = (string) ($isKnockoutStage ? ($match['round_name'] ?? '-') : ($match['group_name'] ?? '-'));
$courtNumber = (int) ($match['court_number'] ?? 0);
$courtDisplay = $courtNumber > 0 ? ('Court ' . $courtNumber) : ($isKnockoutStage ? 'TBD' : '-');
$subtitleParts = [
    $contextValue !== '' ? $contextLabel . ' ' . $contextValue : $contextLabel,
    $courtDisplay,
    ($isKnockoutStage ? 'Estimated start ' : 'Planned start ') . $plannedStartDisplay,
];
?>
<section class="bb-match-detail-workspace">
    <header class="bb-workspace-header bb-match-detail-header">
        <div>
            <span class="bb-section-kicker"><?= $isKnockoutStage ? 'Knockout scorekeeping' : 'Group scorekeeping' ?></span>
            <h2><?= $isKnockoutStage ? 'Knockout Match Detail' : 'Group Match Detail' ?></h2>
            <p><?= htmlspecialchars(implode(' | ', $subtitleParts), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= htmlspecialchars($backToMatchesUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Back to matches</a>
    </header>

    <section class="bb-match-detail-hero" aria-label="Match summary">
        <div class="bb-match-side <?= $teamAWon ? 'bb-match-side-winner' : '' ?>">
            <span class="bb-match-side-label">Team A</span>
            <strong><?= htmlspecialchars($teamAName, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if ($teamAWon): ?>
                <span class="bb-winner-badge">W</span>
            <?php endif; ?>
        </div>

        <div class="bb-match-score-center">
            <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= htmlspecialchars($resultDisplay, ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= htmlspecialchars($status === 'finished' ? $setSummaryDisplay : $matchModeLabel, ENT_QUOTES, 'UTF-8') ?></small>
            <div class="bb-match-hero-meta">
                <span><?= htmlspecialchars($matchModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars($courtDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars($plannedStartDisplay, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="bb-match-side <?= $teamBWon ? 'bb-match-side-winner' : '' ?>">
            <span class="bb-match-side-label">Team B</span>
            <strong><?= htmlspecialchars($teamBName, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if ($teamBWon): ?>
                <span class="bb-winner-badge">W</span>
            <?php endif; ?>
        </div>
    </section>

    <div class="bb-match-detail-grid">
        <aside class="bb-match-actions-card">
            <div class="bb-workspace-card-header">
                <div>
                    <span class="bb-section-kicker">Control</span>
                    <h3>Match Control</h3>
                </div>
            </div>

            <?php if (!$isKnockoutStage && is_string($startActionUrl ?? null) && $startActionUrl !== ''): ?>
                <div class="bb-match-action-block">
                    <strong>Start match</strong>
                    <?php if ($status === 'scheduled'): ?>
                        <p>Move this match into scorekeeping when play begins.</p>
                        <form method="post" action="<?= htmlspecialchars($startActionUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                            <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                            <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                            <button type="submit" class="btn btn-primary w-100">Start match</button>
                        </form>
                    <?php else: ?>
                        <p class="mb-0">Match can only be started from status <code>scheduled</code>.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bb-match-action-block">
                    <strong>Current status</strong>
                    <p class="mb-0">Use the score entry panel to record or correct this result.</p>
                </div>
            <?php endif; ?>

            <?php if ($status === 'finished'): ?>
                <div class="bb-match-action-block">
                    <strong>Result correction</strong>
                    <p>Saving again will update the recorded result.</p>
                    <?php if (!$isKnockoutStage && is_string($resetActionUrl ?? null) && $resetActionUrl !== ''): ?>
                        <form method="post" action="<?= htmlspecialchars($resetActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Reset result and set match back to scheduled?');">
                            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                            <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                            <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">Reset result</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($requiresDependentResetConfirmation): ?>
                <div class="bb-match-action-block bb-match-action-warning">
                    <strong>Knockout dependency</strong>
                    <p class="mb-0">Changing this result will reset dependent knockout matches after confirmation.</p>
                </div>
            <?php endif; ?>
        </aside>

        <section class="bb-score-entry-card">
            <div class="bb-workspace-card-header">
                <div>
                    <span class="bb-section-kicker">Score</span>
                    <h3>Score Entry</h3>
                    <p><?= htmlspecialchars($matchModeLabel, ENT_QUOTES, 'UTF-8') ?> scoring for this match.</p>
                </div>
            </div>

            <?php if ($scoreEntryAllowed): ?>
                <form
                    method="post"
                    action="<?= htmlspecialchars($scoreActionUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="bb-score-form"
                    <?= $requiresDependentResetConfirmation ? 'onsubmit="if(!confirm(\'Changing this result will reset dependent knockout matches. Continue?\')){return false;} var input=this.querySelector(\'input[name=confirm_reset_dependents]\'); if(input){input.value=\'1\';}"' : '' ?>
                >
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <?php if (!$isKnockoutStage): ?>
                        <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                        <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="confirm_reset_dependents" value="0">

                    <div class="bb-score-entry-head" aria-hidden="true">
                        <span>Set</span>
                        <span><?= htmlspecialchars($teamAName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($teamBName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="bb-score-set-list">
                        <?php for ($set = 1; $set <= $maxSetNumber; $set++): ?>
                            <?php $isRequired = $set <= 2; ?>
                            <div class="bb-score-set-row">
                                <div class="bb-score-set-label">
                                    <strong>Set <?= $set ?></strong>
                                    <small><?= $isRequired ? 'Required' : 'Optional' ?></small>
                                </div>
                                <label class="bb-score-input-wrap" for="set-<?= $set ?>-a">
                                    <span><?= htmlspecialchars($teamAName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="99"
                                        class="form-control bb-score-input"
                                        id="set-<?= $set ?>-a"
                                        name="set_<?= $set ?>_a"
                                        value="<?= htmlspecialchars(isset($setValues[$set]) ? (string) $setValues[$set]['score_a'] : '', ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isRequired ? 'required' : '' ?>
                                    >
                                </label>
                                <label class="bb-score-input-wrap" for="set-<?= $set ?>-b">
                                    <span><?= htmlspecialchars($teamBName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="99"
                                        class="form-control bb-score-input"
                                        id="set-<?= $set ?>-b"
                                        name="set_<?= $set ?>_b"
                                        value="<?= htmlspecialchars(isset($setValues[$set]) ? (string) $setValues[$set]['score_b'] : '', ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isRequired ? 'required' : '' ?>
                                    >
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="bb-score-help">
                        <?php if ($matchMode === 'best_of_3'): ?>
                            <p>Enter 2 sets for a 2:0 finish, or add set 3 for a 2:1 finish.</p>
                        <?php endif; ?>
                        <?php if ($status === 'finished'): ?>
                            <p>This match is finished. Saving will correct the existing result.</p>
                        <?php endif; ?>
                        <?php if ($requiresDependentResetConfirmation): ?>
                            <p class="text-warning">Changing this result will reset dependent knockout matches.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bb-score-submit">
                        <button type="submit" class="btn btn-success">Save result and finish match</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="bb-empty-state">
                    Score entry is available for scheduled, in-progress, and finished matches.
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
