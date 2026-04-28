<?php

declare(strict_types=1);

/** @var array<string, mixed> $tournament */
/** @var string $publicViewSettingsActionUrl */
/** @var string $publicDisplayUrl */
/** @var list<array{
 *     key: string,
 *     label: string,
 *     is_enabled: int,
 *     sort_order: int,
 *     path: string,
 *     direct_url: string
 * }> $publicScreenSettings */

$tournamentId = (int) ($tournament['id'] ?? 0);
$publicViewEnabled = (int) ($tournament['public_view_enabled'] ?? 0) > 0;
$autoplayEnabled = (int) ($tournament['autoplay_enabled'] ?? 1) > 0;
$rotationIntervalSeconds = (int) ($tournament['rotation_interval_seconds'] ?? 15);
$publicTitleOverride = (string) ($tournament['public_title_override'] ?? '');
$publicDescription = (string) ($tournament['public_description'] ?? '');
$publicLogoPath = trim((string) ($tournament['public_logo_path'] ?? ''));
$publicMapUrl = (string) ($tournament['public_map_url'] ?? '');
$publicMapEmbedUrl = (string) ($tournament['public_map_embed_url'] ?? '');
$screenHelpText = [
    'overview' => 'Tournament invitation screen with name, date, start time, location, logo, description, map and QR code.',
    'next_matches' => 'Matches in progress and upcoming matches grouped by court.',
    'standings' => 'Current group tables.',
    'group_schedule' => 'Full group-stage schedule with results and courts.',
    'knockout' => 'Knockout matches overview with winners and pending matches.',
    'recent_results' => 'Recently finished matches with scores and winners.',
];
?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($publicViewSettingsActionUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="public_view">
            <input type="hidden" name="public_view_form" value="general">

            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="public_view_enabled" name="public_view_enabled" value="1" <?= $publicViewEnabled ? 'checked' : '' ?>>
                <label class="form-check-label" for="public_view_enabled">Enable Public View</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="autoplay_enabled" name="autoplay_enabled" value="1" <?= $autoplayEnabled ? 'checked' : '' ?>>
                <label class="form-check-label" for="autoplay_enabled">Enable autoplay display rotation</label>
            </div>
            <div class="mb-3 col-12 col-md-4 ps-0">
                <label for="rotation_interval_seconds" class="form-label">Rotation interval (seconds)</label>
                <input type="number" class="form-control" name="rotation_interval_seconds" id="rotation_interval_seconds" min="5" max="300" value="<?= $rotationIntervalSeconds ?>" required>
            </div>
            <div class="mb-2">
                <label for="public_title_override" class="form-label">Public title override (optional)</label>
                <input type="text" class="form-control" name="public_title_override" id="public_title_override" maxlength="200" value="<?= htmlspecialchars($publicTitleOverride, ENT_QUOTES, 'UTF-8') ?>" placeholder="Leave empty to use tournament name">
            </div>
            <div class="mb-2">
                <label for="public_description" class="form-label">Public description (optional)</label>
                <textarea class="form-control" name="public_description" id="public_description" rows="3" maxlength="3000" placeholder="Welcome message, schedule notes, or practical info..."><?= htmlspecialchars($publicDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="mb-2">
                <label for="public_map_url" class="form-label">Map URL (optional)</label>
                <input type="url" class="form-control" name="public_map_url" id="public_map_url" maxlength="500" value="<?= htmlspecialchars($publicMapUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://maps.google.com/...">
                <div class="form-text">Use a normal Google Maps share link. This opens the map in a new tab.</div>
            </div>
            <div class="mb-2">
                <label for="public_map_embed_url" class="form-label">Map embed (optional)</label>
                <textarea class="form-control" name="public_map_embed_url" id="public_map_embed_url" rows="2" maxlength="5000" placeholder="https://www.google.com/maps/embed?pb=... or full iframe code"><?= htmlspecialchars($publicMapEmbedUrl, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">Use Google Maps -> Share -> Embed a map. You can paste either the full iframe code or only the iframe src URL.</div>
            </div>
            <div class="mb-3">
                <label for="public_logo" class="form-label">Tournament logo (optional)</label>
                <input type="file" class="form-control" name="public_logo" id="public_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                <div class="form-text">Allowed: PNG, JPG, WEBP. Max size: 2 MB.</div>
                <?php if ($publicLogoPath !== ''): ?>
                    <div class="mt-2">
                        <img src="<?= htmlspecialchars($url('/' . ltrim($publicLogoPath, '/')), ENT_QUOTES, 'UTF-8') ?>" alt="Current tournament logo" style="max-height: 80px;" class="img-thumbnail">
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save Public View settings</button>
            <a href="<?= htmlspecialchars($publicDisplayUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary ms-2">Open Display</a>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <form method="post" action="<?= htmlspecialchars($publicViewSettingsActionUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
        <input type="hidden" name="return_section" value="public_view">
        <input type="hidden" name="public_view_form" value="screen_list">
        <input type="hidden" name="public_view_enabled" value="<?= $publicViewEnabled ? '1' : '0' ?>">
        <input type="hidden" name="autoplay_enabled" value="<?= $autoplayEnabled ? '1' : '0' ?>">
        <input type="hidden" name="rotation_interval_seconds" value="<?= $rotationIntervalSeconds ?>">
        <input type="hidden" name="public_title_override" value="<?= htmlspecialchars($publicTitleOverride, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="public_description" value="<?= htmlspecialchars($publicDescription, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="public_map_url" value="<?= htmlspecialchars($publicMapUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="public_map_embed_url" value="<?= htmlspecialchars($publicMapEmbedUrl, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Screen</th>
                        <th style="width: 70px;">Info</th>
                        <th>Enabled</th>
                        <th>Sort order</th>
                        <th>Direct link</th>
                        <th>Preview</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($publicScreenSettings) === 0): ?>
                        <tr><td colspan="6" class="text-muted text-center py-3">No public screens configured.</td></tr>
                    <?php else: ?>
                        <?php foreach ($publicScreenSettings as $screen): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $screen['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <?php $helpText = (string) ($screenHelpText[(string) ($screen['key'] ?? '')] ?? ''); ?>
                                    <?php if ($helpText !== ''): ?>
                                        <span
                                            class="badge rounded-pill text-bg-light border text-secondary"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-title="<?= htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8') ?>"
                                            style="cursor: help;"
                                            aria-label="Screen help"
                                        >i</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="hidden" name="screen_enabled[<?= htmlspecialchars((string) $screen['key'], ENT_QUOTES, 'UTF-8') ?>]" value="0">
                                    <input class="form-check-input" type="checkbox" name="screen_enabled[<?= htmlspecialchars((string) $screen['key'], ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= ((int) $screen['is_enabled'] > 0) ? 'checked' : '' ?>>
                                </td>
                                <td style="max-width: 120px;">
                                    <input type="number" class="form-control form-control-sm" min="1" max="99" name="screen_order[<?= htmlspecialchars((string) $screen['key'], ENT_QUOTES, 'UTF-8') ?>]" value="<?= (int) $screen['sort_order'] ?>">
                                </td>
                                <td>
                                    <code><?= htmlspecialchars((string) $screen['direct_url'], ENT_QUOTES, 'UTF-8') ?></code>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= htmlspecialchars((string) $screen['direct_url'], ENT_QUOTES, 'UTF-8') ?>">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($publicScreenSettings) > 0): ?>
                <div class="p-3 border-top">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Save screen list</button>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>
