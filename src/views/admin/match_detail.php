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
$matchMode = (string) ($match['match_mode'] ?? ($tournament['group_stage_mode'] ?? ($tournament['match_mode'] ?? '')));
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
        $plannedStartDisplay = $plannedStartDate->format('Y-m-d H:i');
    }
}
$scoreEntryAllowed = in_array($status, ['scheduled', 'in_progress', 'finished'], true);
$maxSetNumber = $matchMode === 'fixed_2_sets' ? 2 : 3;
$setValues = [];
foreach ($matchSets as $set) {
    $setNumber = (int) ($set['set_number'] ?? 0);
    if ($setNumber <= 0) {
        continue;
    }

    $setValues[$setNumber] = [
        'score_a' => (int) ($set['score_a'] ?? 0),
        'score_b' => (int) ($set['score_b'] ?? 0),
    ];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0"><?= $isKnockoutStage ? 'Knockout match detail' : 'Group match detail' ?></h1>
    <a href="<?= htmlspecialchars($backToMatchesUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <?php if ($isKnockoutStage): ?>
                <div class="col-12 col-md-6">
                    <div class="small text-muted">Round</div>
                    <div class="fw-semibold"><?= htmlspecialchars((string) ($match['round_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php else: ?>
                <div class="col-12 col-md-6">
                    <div class="small text-muted">Group</div>
                    <div class="fw-semibold"><?= htmlspecialchars((string) ($match['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Match mode</div>
                <div><span class="badge text-bg-secondary"><?= htmlspecialchars($matchMode, ENT_QUOTES, 'UTF-8') ?></span></div>
            </div>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Team A</div>
                <div class="fw-semibold"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Team B</div>
                <div class="fw-semibold"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Court</div>
                <div><?= (int) ($match['court_number'] ?? 0) > 0 ? ('Court ' . (int) $match['court_number']) : ($isKnockoutStage ? 'TBD' : '-') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted"><?= $isKnockoutStage ? 'Estimated start' : 'Planned start' ?></div>
                <div><?= htmlspecialchars($plannedStartDisplay, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Status</div>
                <div><span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isKnockoutStage && is_string($startActionUrl ?? null) && $startActionUrl !== ''): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5 mb-2">Start match</h2>
            <?php if ($status === 'scheduled'): ?>
                <form method="post" action="<?= htmlspecialchars($startActionUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                    <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                    <button type="submit" class="btn btn-primary">Start match</button>
                </form>
            <?php else: ?>
                <p class="text-muted mb-0">Match can only be started from status <code>scheduled</code>.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-2">Score entry</h2>
        <?php if ($scoreEntryAllowed): ?>
            <form
                method="post"
                action="<?= htmlspecialchars($scoreActionUrl, ENT_QUOTES, 'UTF-8') ?>"
                <?= $requiresDependentResetConfirmation ? 'onsubmit="if(!confirm(\'Changing this result will reset dependent knockout matches. Continue?\')){return false;} var input=this.querySelector(\'input[name=confirm_reset_dependents]\'); if(input){input.value=\'1\';}"' : '' ?>
            >
                <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                <?php if (!$isKnockoutStage): ?>
                    <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                    <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                <?php endif; ?>
                <input type="hidden" name="confirm_reset_dependents" value="0">
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Set</th>
                            <th><?= htmlspecialchars((string) ($match['team_a_name'] ?? 'Team A'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars((string) ($match['team_b_name'] ?? 'Team B'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($set = 1; $set <= $maxSetNumber; $set++): ?>
                            <?php $isRequired = $set <= 2; ?>
                            <tr>
                                <td>Set <?= $set ?></td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="99"
                                        class="form-control form-control-sm"
                                        name="set_<?= $set ?>_a"
                                        value="<?= htmlspecialchars(isset($setValues[$set]) ? (string) $setValues[$set]['score_a'] : '', ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isRequired ? 'required' : '' ?>
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="99"
                                        class="form-control form-control-sm"
                                        name="set_<?= $set ?>_b"
                                        value="<?= htmlspecialchars(isset($setValues[$set]) ? (string) $setValues[$set]['score_b'] : '', ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isRequired ? 'required' : '' ?>
                                    >
                                </td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($matchMode === 'best_of_3'): ?>
                    <p class="small text-muted mb-2">Enter 2 sets for a 2:0 finish, or add set 3 for a 2:1 finish.</p>
                <?php endif; ?>
                <?php if ($requiresDependentResetConfirmation): ?>
                    <p class="small text-warning mb-2">Changing this result will reset dependent knockout matches.</p>
                <?php endif; ?>
                <button type="submit" class="btn btn-success">Save result and finish match</button>
            </form>
            <?php if (!$isKnockoutStage && $status === 'finished' && is_string($resetActionUrl ?? null) && $resetActionUrl !== ''): ?>
                <hr>
                <form method="post" action="<?= htmlspecialchars($resetActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Reset result and set match back to scheduled?');">
                    <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                    <input type="hidden" name="group_id" value="<?= (int) ($filters['group_id'] ?? 0) ?>">
                    <input type="hidden" name="court" value="<?= (int) ($filters['court'] ?? 0) ?>">
                    <button type="submit" class="btn btn-outline-danger">Reset result</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted mb-0">Score entry is available for scheduled, in-progress, and finished matches.</p>
        <?php endif; ?>
    </div>
</div>
