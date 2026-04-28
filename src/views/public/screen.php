<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var string $screenTitle */
/** @var string $screenKey */
/** @var string $nowLabel */
/** @var string $qrUrl */
/** @var string $currentUrl */
/** @var bool $autoplay */
/** @var int $autoplay_seconds */
/** @var string $autoplay_next_url */
/** @var list<array{key: string, label: string, path: string, is_enabled: int, sort_order: int, url: string}> $enabledScreens */

$startTime = (string) ($tournament['start_time'] ?? '');
if ($startTime !== '' && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $startTime) === 1) {
    $startTime = substr($startTime, 0, 5);
}

$courtBadgeClasses = [
    'text-bg-primary',
    'text-bg-success',
    'text-bg-info',
    'text-bg-warning',
    'text-bg-danger',
    'text-bg-secondary',
    'text-bg-dark',
];
$statusBadgeClass = static function (string $status): string {
    if ($status === 'scheduled') {
        return 'text-bg-primary';
    }
    if ($status === 'in_progress') {
        return 'text-bg-warning';
    }
    if ($status === 'finished') {
        return 'text-bg-success';
    }
    return 'text-bg-secondary';
};
$stageLabel = static function (array $match): string {
    $stage = (string) ($match['stage'] ?? '');
    if ($stage === 'group') {
        $groupName = trim((string) ($match['group_name'] ?? ''));
        return $groupName !== '' ? ('Group ' . $groupName) : 'Group Stage';
    }
    $roundName = trim((string) ($match['round_name'] ?? ''));
    if ($roundName === '') {
        return 'Knockout';
    }
    $position = (int) ($match['bracket_position'] ?? 0);
    if (strcasecmp($roundName, 'Final') === 0) {
        return 'Final';
    }
    return trim($roundName . ($position > 0 ? ' ' . $position : ''));
};
$setSummaryText = static function (array $match): string {
    $summary = trim((string) ($match['set_scores_summary'] ?? ''));
    return $summary;
};
$winnerClassForTeam = static function (array $match, string $side): string {
    $status = (string) ($match['status'] ?? '');
    if ($status !== 'finished') {
        return '';
    }
    $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
    if ($winnerTeamId <= 0) {
        return '';
    }
    $teamId = $side === 'a' ? (int) ($match['team_a_id'] ?? 0) : (int) ($match['team_b_id'] ?? 0);
    return $winnerTeamId === $teamId ? 'public-winner-name' : '';
};
$isWinnerForTeam = static function (array $match, string $side): bool {
    $status = (string) ($match['status'] ?? '');
    if ($status !== 'finished') {
        return false;
    }
    $winnerTeamId = (int) ($match['winner_team_id'] ?? 0);
    if ($winnerTeamId <= 0) {
        return false;
    }
    $teamId = $side === 'a' ? (int) ($match['team_a_id'] ?? 0) : (int) ($match['team_b_id'] ?? 0);
    return $winnerTeamId === $teamId;
};
$formatMatchTime = static function (?string $raw): string {
    $value = trim((string) $raw);
    if ($value === '') {
        return 'TBD';
    }
    $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    if ($dateTime instanceof \DateTimeImmutable) {
        return $dateTime->format('H:i');
    }
    if (preg_match('/\b([01]\d|2[0-3]):[0-5]\d\b/', $value, $matches) === 1) {
        return (string) ($matches[0] ?? 'TBD');
    }
    return 'TBD';
};
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <div class="public-title"><?= htmlspecialchars((string) ($tournament['name'] ?? 'Tournament'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="public-subtitle text-muted"><?= htmlspecialchars($screenTitle, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="small text-secondary">
            <?= htmlspecialchars((string) ($tournament['event_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <?= $startTime !== '' ? ' | ' . htmlspecialchars($startTime, ENT_QUOTES, 'UTF-8') : '' ?>
            <?= ((string) ($tournament['location'] ?? '')) !== '' ? ' | ' . htmlspecialchars((string) $tournament['location'], ENT_QUOTES, 'UTF-8') : '' ?>
        </div>
        <div class="small text-secondary">Now: <?= htmlspecialchars($nowLabel, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($screenKey !== 'overview'): ?>
        <?php require __DIR__ . '/_qr.php'; ?>
    <?php endif; ?>
</div>

<?php if (count($enabledScreens) > 1): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($enabledScreens as $screen): ?>
            <a href="<?= htmlspecialchars((string) $screen['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $screen['key'] === $screenKey ? 'btn-dark' : 'btn-outline-dark' ?>">
                <?= htmlspecialchars((string) $screen['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($screenKey === 'overview'): ?>
    <?php
    $overviewTitle = trim((string) ($tournament['public_title_override'] ?? ''));
    if ($overviewTitle === '') {
        $overviewTitle = (string) ($tournament['name'] ?? 'Tournament');
    }
    $overviewDescription = trim((string) ($tournament['public_description'] ?? ''));
    $overviewLogoPath = trim((string) ($tournament['public_logo_path'] ?? ''));
    $overviewMapButtonUrl = trim((string) ($overviewMapButtonUrl ?? ''));
    $overviewMapEmbedUrl = trim((string) ($overviewMapEmbedUrl ?? ''));
    $logoUrl = $overviewLogoPath !== '' ? $url('/' . ltrim($overviewLogoPath, '/')) : '';
    ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-8">
                    <h2 class="display-5 fw-bold mb-3"><?= htmlspecialchars($overviewTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="d-flex flex-column gap-2 fs-5">
                        <div><span class="text-muted">Date:</span> <strong><?= htmlspecialchars((string) ($tournament['event_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div><span class="text-muted">Start:</span> <strong><?= htmlspecialchars($startTime !== '' ? $startTime : '-', ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div><span class="text-muted">Location:</span> <strong><?= htmlspecialchars((string) ($tournament['location'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div><span class="text-muted">Current time:</span> <strong><?= htmlspecialchars($nowLabel, ENT_QUOTES, 'UTF-8') ?></strong></div>
                    </div>
                    <?php if ($overviewDescription !== ''): ?>
                        <div class="mt-4 p-3 bg-light rounded border">
                            <div class="small text-muted mb-1">About This Tournament</div>
                            <div class="fs-5"><?= nl2br(htmlspecialchars($overviewDescription, ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($overviewMapButtonUrl !== ''): ?>
                        <div class="mt-4">
                            <a href="<?= htmlspecialchars($overviewMapButtonUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-lg btn-outline-primary">Open map</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-lg-4">
                    <?php if ($logoUrl !== ''): ?>
                        <div class="text-center mb-3">
                            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Tournament logo" class="img-fluid rounded shadow-sm bg-white p-2" style="max-height: 220px;">
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center">
                        <?php require __DIR__ . '/_qr.php'; ?>
                    </div>
                </div>
            </div>
            <?php if ($overviewMapEmbedUrl !== ''): ?>
                <div class="mt-4">
                    <iframe
                        src="<?= htmlspecialchars($overviewMapEmbedUrl, ENT_QUOTES, 'UTF-8') ?>"
                        width="100%"
                        height="350"
                        style="border:0;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                        title="Tournament map"
                    ></iframe>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($screenKey === 'next_matches'): ?>
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">In Progress</div>
                <div class="table-responsive">
                    <?php if (!is_array($in_progress_matches ?? null) || count($in_progress_matches) === 0): ?>
                        <p class="text-muted mb-0 p-3">No matches in progress.</p>
                    <?php else: ?>
                        <table class="table table-sm table-striped mb-0 public-table">
                            <thead><tr><th>Match</th><th>Court</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($in_progress_matches as $match): ?>
                                <?php
                                $court = (int) ($match['court_number'] ?? 0);
                                $badgeClass = $court > 0 ? ($courtBadgeClasses[($court - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                                $status = (string) ($match['status'] ?? 'pending');
                                ?>
                                <tr class="public-match-row">
                                    <td>
                                        <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'a'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isWinnerForTeam($match, 'a')): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                        vs
                                        <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'b'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isWinnerForTeam($match, 'b')): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?= htmlspecialchars($stageLabel($match), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <?php if ($court > 0): ?>
                                            <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $court ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= htmlspecialchars($statusBadgeClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Next Scheduled</div>
                <div class="table-responsive">
                    <?php if (!is_array($next_matches ?? null) || count($next_matches) === 0): ?>
                        <p class="text-muted mb-0 p-3">No scheduled matches.</p>
                    <?php else: ?>
                        <table class="table table-sm table-striped mb-0 public-table">
                            <thead><tr><th>Match</th><th>Court</th><th>Time</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($next_matches as $match): ?>
                                <?php
                                $time = $formatMatchTime((string) ($match['planned_start'] ?? ''));
                                $court = (int) ($match['court_number'] ?? 0);
                                $badgeClass = $court > 0 ? ($courtBadgeClasses[($court - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                                $status = (string) ($match['status'] ?? 'pending');
                                ?>
                                <tr class="public-match-row">
                                    <td>
                                        <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'a'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isWinnerForTeam($match, 'a')): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                        vs
                                        <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'b'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isWinnerForTeam($match, 'b')): ?>
                                            <span class="badge text-bg-light border text-secondary">W</span>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?= htmlspecialchars($stageLabel($match), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <?php if ($court > 0): ?>
                                            <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $court ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= htmlspecialchars($statusBadgeClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($screenKey === 'standings'): ?>
    <?php
    $groupNameById = [];
    if (is_array($groups ?? null)) {
        foreach ($groups as $groupRow) {
            $gid = (int) ($groupRow['id'] ?? 0);
            if ($gid > 0) {
                $groupNameById[$gid] = (string) ($groupRow['name'] ?? ('Group ' . $gid));
            }
        }
    }
    ?>
    <?php if (!is_array($groupStandingsByGroup ?? null) || count($groupStandingsByGroup) === 0): ?>
        <p class="text-muted">No standings available yet.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($groupStandingsByGroup as $groupId => $rows): ?>
                <div class="col-12 col-xxl-6">
                    <div class="card shadow-sm">
                        <div class="card-header fw-semibold">Group <?= htmlspecialchars((string) ($groupNameById[(int) $groupId] ?? $groupId), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 public-table public-standings">
                                <thead><tr><th class="col-rank">#</th><th class="col-team">Team</th><th class="col-num">P</th><th class="col-num">W</th><th class="col-num">D</th><th class="col-num">L</th><th class="col-num">Pts</th><th class="col-num">Diff</th></tr></thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td class="col-rank"><?= (int) ($row['position'] ?? 0) ?></td>
                                        <td class="col-team"><?= htmlspecialchars((string) ($row['team_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="col-num"><?= (int) ($row['played'] ?? 0) ?></td>
                                        <td class="col-num"><?= (int) ($row['wins'] ?? 0) ?></td>
                                        <td class="col-num"><?= (int) ($row['draws'] ?? 0) ?></td>
                                        <td class="col-num"><?= (int) ($row['losses'] ?? 0) ?></td>
                                        <td class="col-num"><?= (int) ($row['tournament_points'] ?? 0) ?></td>
                                        <td class="col-num"><?= (int) ($row['point_diff'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php elseif ($screenKey === 'group_schedule'): ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 public-table">
                <thead><tr><th>Order</th><th>Group</th><th>Time</th><th>Court</th><th>Team A</th><th>Team B</th><th>Result</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!is_array($groupMatches ?? null) || count($groupMatches) === 0): ?>
                    <tr><td colspan="8" class="text-muted text-center py-3">No group matches.</td></tr>
                <?php else: foreach ($groupMatches as $match): ?>
                    <?php
                    $status = (string) ($match['status'] ?? 'pending');
                    $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                    $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                    $court = (int) ($match['court_number'] ?? 0);
                    $courtBadgeClass = $court > 0 ? ($courtBadgeClasses[($court - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                    $setScoresSummary = $setSummaryText($match);
                    ?>
                    <tr>
                        <td><?= (int) ($match['schedule_order'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($match['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($formatMatchTime((string) ($match['planned_start'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($court > 0): ?>
                                <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $court ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'a'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'a')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'b'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'b')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($status === 'finished' ? ($setsSummaryA . ':' . $setsSummaryB) : '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                                <div class="public-result-sub">(<?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= htmlspecialchars($statusBadgeClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($screenKey === 'knockout'): ?>
    <?php
    $sourceLabelByCode = [];
    $matchLabelsByIndex = [];
    $roundIndex = 0;
    $currentRoundName = '';
    $matchNumberInRound = 0;
    if (is_array($knockoutMatches ?? null)) {
        foreach ($knockoutMatches as $index => $match) {
            $roundName = trim((string) ($match['round_name'] ?? ''));
            if ($roundName !== $currentRoundName) {
                $currentRoundName = $roundName;
                $roundIndex++;
                $matchNumberInRound = 0;
            }
            $matchNumberInRound++;
            $position = (int) ($match['bracket_position'] ?? 0);
            $label = strcasecmp($roundName, 'Final') === 0 ? 'Final' : trim($roundName . ' ' . $position);
            $matchLabelsByIndex[$index] = $label;
            $sourceLabelByCode['winner:r' . $roundIndex . ':m' . $matchNumberInRound] = $label;
        }
    }
    ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 public-table">
                <thead><tr><th>Match</th><th>Team A</th><th>Team B</th><th>Court</th><th>Estimated start</th><th>Result</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!is_array($knockoutMatches ?? null) || count($knockoutMatches) === 0): ?>
                    <tr><td colspan="7" class="text-muted text-center py-3">No knockout matches.</td></tr>
                <?php else: foreach ($knockoutMatches as $index => $match): ?>
                    <?php
                    $sourceA = trim((string) ($match['team_a_source'] ?? ''));
                    $sourceB = trim((string) ($match['team_b_source'] ?? ''));
                    $sourceALabel = ($sourceA !== '' && isset($sourceLabelByCode[$sourceA])) ? ('Winner of ' . $sourceLabelByCode[$sourceA]) : $sourceA;
                    $sourceBLabel = ($sourceB !== '' && isset($sourceLabelByCode[$sourceB])) ? ('Winner of ' . $sourceLabelByCode[$sourceB]) : $sourceB;
                    $status = (string) ($match['status'] ?? 'pending');
                    $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                    $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                    $setScoresSummary = $setSummaryText($match);
                    $court = (int) ($match['court_number'] ?? 0);
                    $courtBadgeClass = $court > 0 ? ($courtBadgeClasses[($court - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                    $estimatedStartDisplay = $formatMatchTime((string) ($match['planned_start'] ?? ''));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($matchLabelsByIndex[$index] ?? 'Match'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'a'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'a')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                            <?= $sourceA !== '' ? '<div class="small text-muted">' . htmlspecialchars($sourceALabel, ENT_QUOTES, 'UTF-8') . '</div>' : '' ?>
                        </td>
                        <td>
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'b'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'b')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                            <?= $sourceB !== '' ? '<div class="small text-muted">' . htmlspecialchars($sourceBLabel, ENT_QUOTES, 'UTF-8') . '</div>' : '' ?>
                        </td>
                        <td>
                            <?php if ($court > 0): ?>
                                <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $court ?></span>
                            <?php else: ?>
                                <span class="text-muted">TBD</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($estimatedStartDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($status === 'finished' ? ($setsSummaryA . ':' . $setsSummaryB) : '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($status === 'finished' && $setScoresSummary !== ''): ?>
                                <div class="public-result-sub">(<?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= htmlspecialchars($statusBadgeClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 public-table">
                <thead><tr><th>Stage</th><th>Court</th><th>Teams</th><th>Result</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!is_array($recentResults ?? null) || count($recentResults) === 0): ?>
                    <tr><td colspan="5" class="text-muted text-center py-3">No finished matches yet.</td></tr>
                <?php else: foreach ($recentResults as $match): ?>
                    <?php
                    $status = (string) ($match['status'] ?? 'finished');
                    $setsSummaryA = (int) ($match['sets_summary_a'] ?? 0);
                    $setsSummaryB = (int) ($match['sets_summary_b'] ?? 0);
                    $court = (int) ($match['court_number'] ?? 0);
                    $courtBadgeClass = $court > 0 ? ($courtBadgeClasses[($court - 1) % count($courtBadgeClasses)] ?? 'text-bg-secondary') : 'text-bg-secondary';
                    $setScoresSummary = $setSummaryText($match);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($stageLabel($match), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($court > 0): ?>
                                <span class="badge <?= htmlspecialchars($courtBadgeClass, ENT_QUOTES, 'UTF-8') ?>">Court <?= $court ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'a'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_a_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'a')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                            vs
                            <span class="<?= htmlspecialchars($winnerClassForTeam($match, 'b'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($match['team_b_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isWinnerForTeam($match, 'b')): ?>
                                <span class="badge text-bg-light border text-secondary">W</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $setsSummaryA ?>:<?= $setsSummaryB ?>
                            <?php if ($setScoresSummary !== ''): ?>
                                <div class="public-result-sub">(<?= htmlspecialchars($setScoresSummary, ENT_QUOTES, 'UTF-8') ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= htmlspecialchars($statusBadgeClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($autoplay && $autoplay_seconds > 0 && $autoplay_next_url !== ''): ?>
    <script>
        setTimeout(function () {
            window.location.href = <?= json_encode($autoplay_next_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        }, <?= (int) $autoplay_seconds * 1000 ?>);
    </script>
<?php endif; ?>
