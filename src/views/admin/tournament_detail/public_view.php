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
$publicViewTheme = (string) ($tournament['public_view_theme'] ?? 'dark');
if (!in_array($publicViewTheme, ['dark', 'light'], true)) {
    $publicViewTheme = 'dark';
}
$publicTitleOverride = (string) ($tournament['public_title_override'] ?? '');
$publicDescription = (string) ($tournament['public_description'] ?? '');
$publicLogoPath = trim((string) ($tournament['public_logo_path'] ?? ''));
$publicMapUrl = (string) ($tournament['public_map_url'] ?? '');
$publicMapEmbedUrl = (string) ($tournament['public_map_embed_url'] ?? '');
$enabledScreenCount = 0;
foreach ($publicScreenSettings as $screen) {
    if ((int) ($screen['is_enabled'] ?? 0) > 0) {
        $enabledScreenCount++;
    }
}
$screenHelpText = [
    'overview' => 'Tournament invitation screen with name, date, start time, location, logo, description, map and QR code.',
    'next_matches' => 'Matches in progress and upcoming matches grouped by court.',
    'standings' => 'Current group tables.',
    'group_schedule' => 'Full group-stage schedule with results and courts.',
    'knockout' => 'Knockout matches overview with winners and pending matches.',
    'recent_results' => 'Recently finished matches with scores and winners.',
];
?>
<section class="bb-public-settings-shell">
    <header class="bb-workspace-header bb-public-settings-header">
        <div>
            <span class="bb-section-kicker">Display configuration</span>
            <h2>Public View</h2>
            <p>Configure public tournament screens, display rotation and overview content.</p>
        </div>
        <a href="<?= htmlspecialchars($publicDisplayUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open Display</a>
    </header>

    <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($publicViewSettingsActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="bb-public-settings-form">
        <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
        <input type="hidden" name="return_section" value="public_view">
        <input type="hidden" name="public_view_form" value="general">

        <div class="bb-public-settings-grid">
            <section class="bb-display-control-card">
                <div class="bb-workspace-card-header">
                    <div>
                        <span class="bb-section-kicker">Status</span>
                        <h3>Display Controls</h3>
                        <p>Public access, rotation and display theme.</p>
                    </div>
                    <span class="badge <?= $publicViewEnabled ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $publicViewEnabled ? 'Public enabled' : 'Public disabled' ?></span>
                </div>

                <div class="bb-display-control-grid">
                    <div class="bb-toggle-stack">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="public_view_enabled" name="public_view_enabled" value="1" <?= $publicViewEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="public_view_enabled">Enable Public View</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="autoplay_enabled" name="autoplay_enabled" value="1" <?= $autoplayEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="autoplay_enabled">Enable autoplay display rotation</label>
                        </div>
                    </div>

                    <div class="bb-display-control-fields">
                        <div class="bb-field">
                            <label for="rotation_interval_seconds" class="form-label">Rotation interval</label>
                            <input type="number" class="form-control" name="rotation_interval_seconds" id="rotation_interval_seconds" min="5" max="300" value="<?= $rotationIntervalSeconds ?>" required>
                            <div class="form-text">Seconds between display screens.</div>
                        </div>
                        <div class="bb-field">
                            <label for="public_view_theme" class="form-label">Public View theme</label>
                            <select class="form-select" name="public_view_theme" id="public_view_theme" required>
                                <option value="dark" <?= $publicViewTheme === 'dark' ? 'selected' : '' ?>>Dark broadcast</option>
                                <option value="light" <?= $publicViewTheme === 'light' ? 'selected' : '' ?>>Light outdoor</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bb-public-overview-card">
                <div class="bb-workspace-card-header">
                    <div>
                        <span class="bb-section-kicker">Content</span>
                        <h3>Overview Content</h3>
                        <p>Presentation text and map links for the public overview screen.</p>
                    </div>
                </div>

                <div class="bb-public-overview-grid">
                    <div class="bb-field bb-field-full">
                        <label for="public_title_override" class="form-label">Public title override</label>
                        <input type="text" class="form-control" name="public_title_override" id="public_title_override" maxlength="200" value="<?= htmlspecialchars($publicTitleOverride, ENT_QUOTES, 'UTF-8') ?>" placeholder="Leave empty to use tournament name">
                    </div>
                    <div class="bb-field bb-field-full">
                        <label for="public_description" class="form-label">Public description</label>
                        <textarea class="form-control bb-large-textarea" name="public_description" id="public_description" rows="5" maxlength="3000" placeholder="Welcome message, schedule notes, or practical info..."><?= htmlspecialchars($publicDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="bb-field bb-field-full bb-map-field">
                        <label for="public_map_url" class="form-label">Map URL</label>
                        <input type="url" class="form-control bb-long-input" name="public_map_url" id="public_map_url" maxlength="500" value="<?= htmlspecialchars($publicMapUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://maps.google.com/...">
                        <div class="form-text">Paste a normal Google Maps share link. This opens the map in a new tab.</div>
                    </div>
                    <div class="bb-field bb-field-full bb-map-field">
                        <label for="public_map_embed_url" class="form-label">Map embed</label>
                        <textarea class="form-control bb-large-textarea bb-map-embed-textarea" name="public_map_embed_url" id="public_map_embed_url" rows="6" maxlength="5000" placeholder="https://www.google.com/maps/embed?pb=... or full iframe code"><?= htmlspecialchars($publicMapEmbedUrl, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Paste a Google Maps iframe code or iframe src URL.</div>
                    </div>
                </div>
            </section>

            <section class="bb-branding-card">
                <div class="bb-workspace-card-header">
                    <div>
                        <span class="bb-section-kicker">Branding</span>
                        <h3>Logo</h3>
                        <p>Tournament logo used on the public overview screen.</p>
                    </div>
                </div>

                <div class="bb-branding-layout">
                    <div class="bb-logo-preview-box">
                        <span class="bb-logo-preview-label">Current logo</span>
                        <?php if ($publicLogoPath !== ''): ?>
                            <img src="<?= htmlspecialchars($url('/' . ltrim($publicLogoPath, '/')), ENT_QUOTES, 'UTF-8') ?>" alt="Current tournament logo">
                        <?php else: ?>
                            <strong>No logo uploaded</strong>
                            <span>Optional branding for public screens.</span>
                        <?php endif; ?>
                    </div>
                    <div class="bb-logo-upload-area">
                        <div>
                            <strong>Upload new logo</strong>
                            <span>Replace or add the logo shown on public overview screens.</span>
                        </div>
                        <label for="public_logo" class="form-label">Logo file</label>
                        <input type="file" class="form-control" name="public_logo" id="public_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                        <div class="form-text">Allowed: PNG, JPG, WEBP. Max size: 2 MB.</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="bb-settings-savebar">
            <div>
                <strong>Save display settings</strong>
                <span>Applies public access, presentation and branding changes.</span>
            </div>
            <div class="bb-public-save-actions">
                <a href="<?= htmlspecialchars($publicDisplayUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open Display</a>
                <button type="submit" class="btn btn-primary">Save Public View settings</button>
            </div>
        </div>
    </form>

    <section class="bb-public-screens-card">
        <form method="post" action="<?= htmlspecialchars($publicViewSettingsActionUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tournament_id" value="<?= $tournamentId ?>">
            <input type="hidden" name="return_section" value="public_view">
            <input type="hidden" name="public_view_form" value="screen_list">
            <input type="hidden" name="public_view_enabled" value="<?= $publicViewEnabled ? '1' : '0' ?>">
            <input type="hidden" name="autoplay_enabled" value="<?= $autoplayEnabled ? '1' : '0' ?>">
            <input type="hidden" name="rotation_interval_seconds" value="<?= $rotationIntervalSeconds ?>">
            <input type="hidden" name="public_view_theme" value="<?= htmlspecialchars($publicViewTheme, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="public_title_override" value="<?= htmlspecialchars($publicTitleOverride, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="public_description" value="<?= htmlspecialchars($publicDescription, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="public_map_url" value="<?= htmlspecialchars($publicMapUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="public_map_embed_url" value="<?= htmlspecialchars($publicMapEmbedUrl, ENT_QUOTES, 'UTF-8') ?>">

            <div class="bb-workspace-card-header">
                <div>
                    <span class="bb-section-kicker">Screens</span>
                    <h3>Public Screens</h3>
                    <p>Choose which screens appear in display rotation and in which order. Lower number appears earlier; screens with the same order keep the default order.</p>
                </div>
                <span class="bb-screen-count"><?= $enabledScreenCount ?> / <?= count($publicScreenSettings) ?> enabled</span>
            </div>

            <?php if (count($publicScreenSettings) === 0): ?>
                <div class="bb-empty-state">No public screens configured.</div>
            <?php else: ?>
                <div class="bb-public-screen-list">
                    <?php foreach ($publicScreenSettings as $screen): ?>
                        <?php
                        $screenKey = (string) ($screen['key'] ?? '');
                        $helpText = (string) ($screenHelpText[$screenKey] ?? '');
                        $screenEnabled = (int) ($screen['is_enabled'] ?? 0) > 0;
                        $directUrl = (string) ($screen['direct_url'] ?? '');
                        ?>
                        <div class="bb-public-screen-row">
                            <div class="bb-public-screen-name">
                                <strong><?= htmlspecialchars((string) $screen['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($helpText !== ''): ?>
                                    <span
                                        class="bb-info-pill"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-title="<?= htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Screen help"
                                    >i</span>
                                <?php endif; ?>
                            </div>

                            <div class="bb-public-screen-enabled">
                                <input type="hidden" name="screen_enabled[<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>]" value="0">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="screen_enabled_<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>" name="screen_enabled[<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= $screenEnabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="screen_enabled_<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>">Enabled</label>
                                </div>
                            </div>

                            <div class="bb-public-screen-order">
                                <label class="form-label" for="screen_order_<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>">Order</label>
                                <input type="number" class="form-control form-control-sm" min="1" max="99" id="screen_order_<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>" name="screen_order[<?= htmlspecialchars($screenKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= (int) $screen['sort_order'] ?>">
                                <span>1 = first</span>
                            </div>

                            <code class="bb-direct-link"><?= htmlspecialchars($directUrl, ENT_QUOTES, 'UTF-8') ?></code>

                            <a class="btn btn-sm btn-outline-primary bb-public-screen-open" target="_blank" rel="noopener" href="<?= htmlspecialchars($directUrl, ENT_QUOTES, 'UTF-8') ?>">Open</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bb-screen-savebar">
                    <span>Screen visibility and order are saved separately from display content.</span>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Save screen list</button>
                </div>
            <?php endif; ?>
        </form>
    </section>
</section>
