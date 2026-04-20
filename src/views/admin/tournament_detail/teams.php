<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var list<array<string, mixed>> $teams */
/** @var string $createTeamActionUrl */
/** @var string $updateTeamActionUrl */
/** @var string $deleteTeamActionUrl */

$tournamentId = (int) ($tournament['id'] ?? 0);
?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h5">Add team</h2>
        <form method="post" action="<?= htmlspecialchars($createTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="mb-0">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="teams">
            <div class="mb-2">
                <label for="team_name_new" class="form-label">Team name</label>
                <input type="text" name="team_name" id="team_name_new" class="form-control" required maxlength="150">
            </div>
            <div class="mb-2">
                <label for="team_description_new" class="form-label">Description (optional)</label>
                <textarea name="description" id="team_description_new" class="form-control" rows="2" maxlength="1000"></textarea>
            </div>
            <button type="submit" class="btn btn-outline-primary">Add team</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5">Existing teams</h2>
        <?php if (count($teams) === 0): ?>
            <p class="text-muted">No teams yet.</p>
        <?php endif; ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($teams as $team): ?>
                <?php $teamId = (int) ($team['id'] ?? 0); ?>
                <div class="border rounded p-2 bg-light-subtle">
                    <form method="post" action="<?= htmlspecialchars($updateTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                        <input type="hidden" name="team_id" value="<?= $teamId ?>">
                        <input type="hidden" name="return_section" value="teams">
                        <div class="mb-2">
                            <label class="form-label">Team name</label>
                            <input type="text" name="team_name" class="form-control" required maxlength="150" value="<?= htmlspecialchars((string) ($team['team_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="1000"><?= htmlspecialchars((string) ($team['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </form>
                    <div class="d-flex gap-2 mt-2">
                        <form method="post" action="<?= htmlspecialchars($deleteTeamActionUrl, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Delete this team?');">
                            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                            <input type="hidden" name="team_id" value="<?= $teamId ?>">
                            <input type="hidden" name="confirm_delete" value="1">
                            <input type="hidden" name="return_section" value="teams">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
