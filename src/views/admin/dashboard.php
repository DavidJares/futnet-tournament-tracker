<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $tournaments */
/** @var list<string> $matchModes */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Tournaments</h1>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($tournaments) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No tournaments yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($tournaments as $tournament): ?>
                            <?php
                            $tournamentId = (int) ($tournament['id'] ?? 0);
                            $name = (string) ($tournament['name'] ?? '');
                            $slug = (string) ($tournament['slug'] ?? '');
                            $eventDate = (string) ($tournament['event_date'] ?? '');
                            $mode = (string) ($tournament['match_mode'] ?? '');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($eventDate !== '' ? $eventDate : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url('/admin/tournament?id=' . $tournamentId), ENT_QUOTES, 'UTF-8') ?>">Detail</a>
                                    <form method="post" action="<?= htmlspecialchars($url('/admin/tournaments/delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('Delete this tournament and all related data?');">
                                        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
                                        <input type="hidden" name="confirm_delete" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Create tournament</h2>
                <form method="post" action="<?= htmlspecialchars($url('/admin/tournaments/create'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-2">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="name" required maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" name="slug" id="slug" required pattern="[a-z0-9-]+" maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label for="event_date" class="form-label">Event date</label>
                        <input type="date" class="form-control" name="event_date" id="event_date">
                    </div>
                    <div class="mb-2">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" id="location" maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label for="admin_password" class="form-label">Tournament admin password</label>
                        <input type="password" class="form-control" name="admin_password" id="admin_password" required minlength="8">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="number_of_groups" class="form-label">Groups</label>
                            <input type="number" class="form-control" name="number_of_groups" id="number_of_groups" min="1" max="52" value="2" required>
                        </div>
                        <div class="col-6">
                            <label for="number_of_courts" class="form-label">Courts</label>
                            <input type="number" class="form-control" name="number_of_courts" id="number_of_courts" min="1" max="99" value="1" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <label for="match_duration_minutes" class="form-label">Match duration (min)</label>
                            <input type="number" class="form-control" name="match_duration_minutes" id="match_duration_minutes" min="1" max="240" value="20" required>
                        </div>
                        <div class="col-6">
                            <label for="advancing_teams_count" class="form-label">Advancing teams</label>
                            <input type="number" class="form-control" name="advancing_teams_count" id="advancing_teams_count" min="1" max="64" value="2" required>
                        </div>
                    </div>
                    <div class="mt-2 mb-3">
                        <label for="match_mode" class="form-label">Match mode</label>
                        <select class="form-select" name="match_mode" id="match_mode" required>
                            <?php foreach ($matchModes as $mode): ?>
                                <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create tournament</button>
                </form>
            </div>
        </div>
    </div>
</div>
