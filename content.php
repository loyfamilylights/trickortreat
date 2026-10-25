<?php
require_once __DIR__ . '/lib/config.php';

$totMessage = '';
$totMessageClass = '';
$totAction = isset($_POST['tot_action']) ? $_POST['tot_action'] : '';

function tot_flash($result) {
    global $totMessage, $totMessageClass;
    $totMessage = $result['message'];
    $totMessageClass = !empty($result['ok']) ? 'tot-ok' : 'tot-error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $totSettings = tot_load_settings();

    if ($totAction === 'save') {
        $postedEnv = isset($_POST['environment']) ? $_POST['environment'] : 'prod';
        $totSettings['environment'] = in_array($postedEnv, TOT_ENVIRONMENT_NAMES, true) ? $postedEnv : 'prod';
        foreach (TOT_ENVIRONMENT_NAMES as $envName) {
            $totSettings['environments'][$envName]['cloud_base_url'] = rtrim(trim($_POST[$envName . '_cloud_base_url'] ?? ''), '/');
            $totSettings['environments'][$envName]['token'] = trim($_POST[$envName . '_token'] ?? '');
        }
        $totSettings['cloud_enabled'] = isset($_POST['cloud_enabled']);
        $totSettings['header_text'] = substr(trim($_POST['header_text'] ?? '') ?: 'SPOOKY NEWS', 0, 24);
        $totSettings['display_duration_seconds'] = min(120, max(5, intval($_POST['display_duration_seconds'] ?? 20)));
        $totSettings['local_show_photo'] = isset($_POST['local_show_photo']);
        $totSettings['fpp_base_url'] = rtrim(trim($_POST['fpp_base_url'] ?? 'http://localhost'), '/');
        $totSettings['poll_interval_seconds'] = max(5, intval($_POST['poll_interval_seconds'] ?? 10));
        $totSettings['playlist'] = trim($_POST['playlist'] ?? 'spooky_news');
        $totSettings['ticker_model'] = trim($_POST['ticker_model'] ?? 'TickerZone');
        $totSettings['matrix_width'] = max(1, intval($_POST['matrix_width'] ?? 192));
        $totSettings['photo_zone_height'] = max(1, intval($_POST['photo_zone_height'] ?? 140));
        $totSettings['enabled'] = isset($_POST['enabled']);

        $r = tot_save_settings($totSettings);
        tot_flash($r['ok'] ? array('ok' => true, 'message' => 'Settings saved. The daemon picks up changes within a few seconds (no restart needed).') : $r);
    } elseif ($totAction === 'test_fire') {
        $r = tot_enqueue_local($_POST['test_name'] ?? '', $_POST['test_verdict'] ?? 'treat', $_POST['test_costume'] ?? '');
        if (!empty($r['ok'])) {
            $r['message'] = 'Queued test entry (#' . $r['position'] . ' in line) — watch the matrix.' .
                (tot_daemon_running() ? '' : ' Note: the daemon is NOT running, so nothing will show until it starts (Restart FPPD below).');
        }
        tot_flash($r);
    } elseif ($totAction === 'clear_queue') {
        tot_flash(tot_clear_queue());
    } elseif ($totAction === 'test') {
        tot_flash(tot_test_connection($totSettings));
    } elseif ($totAction === 'restart_fppd') {
        // Deliberately no ?quick=1 -- see tot_fpp_system_action() doc comment.
        tot_flash(tot_fpp_system_action($totSettings, '/api/system/fppd/restart', 8));
    } elseif ($totAction === 'reboot_fpp') {
        if (!isset($_POST['confirm_reboot'])) {
            tot_flash(array('ok' => false, 'message' => 'Reboot not sent — check "Yes, reboot now" to confirm first.'));
        } else {
            tot_flash(tot_fpp_system_action($totSettings, '/api/system/reboot', 5));
        }
    } elseif ($totAction === 'provision') {
        // Same auto-provisioning fpp_install.sh runs on first install.
        $venvPython = TOT_PLUGIN_DIR . '/venv/bin/python3';
        $daemonDir = TOT_PLUGIN_DIR . '/daemon';
        if (!is_executable($venvPython)) {
            tot_flash(array('ok' => false, 'message' => "Plugin venv not found at $venvPython — reinstall the plugin or install its Python dependencies manually."));
        } else {
            $forceFlag = isset($_POST['force_recreate']) ? ' --force-recreate' : '';
            $dryRun = isset($_POST['dry_run']);
            $cmd = 'cd ' . escapeshellarg($daemonDir) . ' && ' . escapeshellarg($venvPython) .
                   ' fpp_provision.py' . $forceFlag . ($dryRun ? ' --dry-run' : '') . ' 2>/dev/null';
            $output = shell_exec($cmd);
            if ($dryRun) {
                $parsed = json_decode(trim((string)$output), true);
                tot_flash($parsed && isset($parsed['message'])
                    ? array('ok' => !empty($parsed['ok']), 'message' => $parsed['message'])
                    : array('ok' => false, 'message' => 'Dry run did not return a result — check the plugin log.'));
            } else {
                $status = tot_read_status();
                $lp = isset($status['last_provision']) ? $status['last_provision'] : null;
                tot_flash($lp && isset($lp['message'])
                    ? array('ok' => !empty($lp['ok']), 'message' => $lp['message'])
                    : array('ok' => false, 'message' => 'Zone setup did not report a result — check the plugin log.'));
            }
        }
    }
}

$totSettings = tot_load_settings();
$totStatus = tot_read_status();
$totActive = tot_active_env($totSettings);
$totCloud = tot_cloud_configured($totSettings);
$totQueue = tot_read_queue();
$totRunning = tot_daemon_running();
$actionUrl = tot_h($_SERVER['REQUEST_URI']);
$doorUrl = tot_door_url();
$last = isset($totStatus['last_item']) ? $totStatus['last_item'] : null;
?>
<style>
  .tot-box { max-width: 680px; }
  .tot-box fieldset { margin-top: 12px; }
  .tot-box label { display: block; margin-top: 10px; font-weight: bold; }
  .tot-box input[type=text], .tot-box input[type=password], .tot-box input[type=number], .tot-box select {
    width: 100%; max-width: 420px; padding: 6px; box-sizing: border-box;
  }
  .tot-box input[type=checkbox], .tot-box input[type=radio] {
    width: 16px !important; height: 16px !important; min-width: 16px; min-height: 16px;
    padding: 0 !important; margin: 0 6px 0 0; vertical-align: middle; box-sizing: border-box;
  }
  .tot-inline { display: inline !important; font-weight: normal !important; }
  .tot-msg { padding: 8px 12px; margin: 10px 0; border-radius: 4px; }
  .tot-ok { background: #e2f7e2; color: #1a5d1a; }
  .tot-error { background: #fde2e2; color: #8a1c1c; }
  .tot-status-table td { padding: 2px 10px 2px 0; vertical-align: top; }
  .tot-banner { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; margin: 0 6px 10px 0; letter-spacing: .5px; color: #fff; }
  .tot-banner.local { background: #5a1a8a; }
  .tot-banner.cloud { background: #b35c00; }
  .tot-banner.run { background: #1a5d1a; }
  .tot-banner.stop { background: #8a1c1c; }
  .tot-door { background: #1b0730; color: #f6ecff; border-radius: 8px; padding: 12px 14px; margin: 8px 0; }
  .tot-door a { color: #ff9a3c; font-weight: bold; word-break: break-all; }
  .tot-queue li { margin: 2px 0; }
  .tot-treat { color: #c25e00; font-weight: bold; }
  .tot-trick { color: #3a8a10; font-weight: bold; }
</style>

<div class="tot-box">
  <h3>&#127875; Trick-or-Treat News — Setup</h3>

  <span class="tot-banner <?php echo $totRunning ? 'run' : 'stop'; ?>">DAEMON: <?php echo $totRunning ? 'RUNNING' : 'NOT RUNNING'; ?></span>
  <?php if ($totCloud): ?>
    <span class="tot-banner cloud">LOCAL + CLOUD (<?php echo tot_h(strtoupper($totActive['name'])); ?>)</span>
  <?php else: ?>
    <span class="tot-banner local">LOCAL ONLY</span>
  <?php endif; ?>

  <?php if ($totMessage): ?>
    <div class="tot-msg <?php echo $totMessageClass; ?>"><?php echo tot_h($totMessage); ?></div>
  <?php endif; ?>

  <div class="tot-door">
    <strong>Door page</strong> — open this on a phone on the same Wi-Fi as this FPP box to send names to the screen:<br>
    <a href="<?php echo tot_h($doorUrl); ?>" target="_blank" id="totDoorLink"><?php echo tot_h($doorUrl); ?></a>
    <script>
      (function () { var a = document.getElementById('totDoorLink'); a.textContent = a.href; })();
    </script>
  </div>

  <fieldset>
    <legend>Test fire</legend>
    <form method="post" action="<?php echo $actionUrl; ?>">
      <input type="hidden" name="tot_action" value="test_fire">
      <label for="test_name">Name</label>
      <input type="text" id="test_name" name="test_name" value="Test Ghoul" maxlength="40">
      <label for="test_costume">Costume (optional)</label>
      <input type="text" id="test_costume" name="test_costume" value="Witch" maxlength="40">
      <p>
        <label class="tot-inline"><input type="radio" name="test_verdict" value="treat" checked> Treat</label>
        &nbsp;&nbsp;
        <label class="tot-inline"><input type="radio" name="test_verdict" value="trick"> Trick</label>
      </p>
      <button type="submit">Send to matrix</button>
    </form>
  </fieldset>

  <fieldset>
    <legend>Waiting in line (<?php echo count($totQueue); ?>)</legend>
    <?php if ($totQueue): ?>
      <ol class="tot-queue">
        <?php foreach ($totQueue as $it): ?>
          <li><?php echo tot_h($it['child_name'] ?? ''); ?>
            <?php if (!empty($it['costume'])): ?> (<?php echo tot_h($it['costume']); ?>)<?php endif; ?>
            — <span class="tot-<?php echo ($it['verdict'] ?? '') === 'trick' ? 'trick' : 'treat'; ?>"><?php echo tot_h(strtoupper($it['verdict'] ?? 'treat')); ?></span>
            <?php if (!empty($it['photo_file'])): ?> &#128247;<?php endif; ?>
            <?php if (!empty($it['attempts'])): ?> <small>(retry <?php echo intval($it['attempts']); ?>)</small><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
      <form method="post" action="<?php echo $actionUrl; ?>" onsubmit="return confirm('Clear everyone waiting in line?');">
        <input type="hidden" name="tot_action" value="clear_queue">
        <button type="submit">Clear line</button>
      </form>
    <?php else: ?>
      <p><small>Nobody waiting. Each entry holds the screen for <?php echo intval($totSettings['display_duration_seconds']); ?>s, then the next one plays.</small></p>
    <?php endif; ?>
  </fieldset>

  <fieldset>
    <legend>Status</legend>
    <table class="tot-status-table">
      <tr><td>Last shown:</td><td><?php echo $last ? tot_h($last['name'] . ' — ' . strtoupper($last['verdict']) . ' (' . $last['source'] . ', ' . $last['at'] . ')') : '—'; ?></td></tr>
      <tr><td>Shown (total):</td><td><?php echo tot_h($totStatus['items_processed_total'] ?? 0); ?></td></tr>
      <tr><td>Daemon heartbeat:</td><td><?php echo tot_h($totStatus['last_heartbeat_at'] ?? 'never'); ?></td></tr>
      <?php if ($totCloud): ?>
      <tr><td>Cloud license:</td><td><strong><?php echo tot_h($totStatus['license'] ?? 'unknown — not polled yet'); ?></strong></td></tr>
      <tr><td>Cloud last poll:</td><td><?php echo tot_h($totStatus['last_poll_at'] ?? 'never'); ?></td></tr>
      <?php endif; ?>
      <tr><td>Last error:</td><td><?php echo tot_h($totStatus['last_error'] ?? '—'); ?></td></tr>
      <tr><td>Plugin version:</td><td><?php echo tot_h($totStatus['plugin_version'] ?? '—'); ?></td></tr>
      <tr><td>FPP version:</td><td><?php echo tot_h($totStatus['fpp_version'] ?? '—'); ?></td></tr>
    </table>
    <p><small>Daemon log: <code>/home/fpp/media/logs/fpp-plugin-trickortreat-daemon.log</code></small></p>
  </fieldset>

  <fieldset>
    <legend>FPP Control</legend>
    <p><small>After installing or updating this plugin, use <strong>Restart FPPD (full)</strong> here — not FPP's own
      Status/Control button, which defaults to a <em>quick</em> restart that doesn't re-run plugin start scripts, so the
      daemon would keep running old code (or never start). If that doesn't do it, reboot the Pi.</small></p>
    <form method="post" action="<?php echo $actionUrl; ?>" style="display:inline-block; margin-right:16px;">
      <input type="hidden" name="tot_action" value="restart_fppd">
      <button type="submit">Restart FPPD (full)</button>
    </form>
    <form method="post" action="<?php echo $actionUrl; ?>" style="display:inline-block;"
          onsubmit="return confirm('Reboot the Pi now? This will interrupt any currently running show.');">
      <input type="hidden" name="tot_action" value="reboot_fpp">
      <label class="tot-inline"><input type="checkbox" name="confirm_reboot"> Yes, reboot now</label>
      <button type="submit" style="margin-left:8px;">Reboot Pi</button>
    </form>
  </fieldset>

  <fieldset>
    <legend>Zone setup</legend>
    <table class="tot-status-table">
      <tr><td>Matrix size:</td><td><?php echo tot_h($totSettings['matrix_width']); ?> x <?php echo tot_h($totSettings['matrix_height']); ?> px</td></tr>
      <tr><td>Photo zone:</td><td><?php echo tot_h($totSettings['photo_model']); ?> — <?php echo tot_h($totSettings['matrix_width']); ?> x <?php echo tot_h($totSettings['photo_zone_height']); ?> px</td></tr>
      <tr><td>Ticker zone:</td><td><?php echo tot_h($totSettings['ticker_model']); ?> — <?php echo tot_h($totSettings['matrix_width']); ?> x <?php echo tot_h($totSettings['ticker_zone_height']); ?> px</td></tr>
      <tr><td>Playlist:</td><td><?php echo tot_h($totSettings['playlist']); ?></td></tr>
    </table>
    <p><small>Auto-detected from your channel outputs at install. If you also run the NaughtyNice plugin, this reuses the same
      PhotoZone/TickerZone. To change the split, resize those models under <strong>Content Setup &rarr; Pixel Overlay Models</strong>
      and click "Re-run zone setup" (no Full reset) — or check Full reset to rebuild both from your current matrix.</small></p>
    <form method="post" action="<?php echo $actionUrl; ?>">
      <input type="hidden" name="tot_action" value="provision">
      <label class="tot-inline"><input type="checkbox" name="force_recreate"> Full reset (delete &amp; rebuild both zones)</label><br>
      <label class="tot-inline"><input type="checkbox" name="dry_run"> Dry run (preview only — writes nothing to FPP)</label>
      <p><button type="submit">Re-run zone setup</button></p>
    </form>
  </fieldset>

  <form method="post" action="<?php echo $actionUrl; ?>">
    <input type="hidden" name="tot_action" value="save">

    <fieldset>
      <legend>Display</legend>
      <label for="header_text">Header text</label>
      <input type="text" id="header_text" name="header_text" maxlength="24" value="<?php echo tot_h($totSettings['header_text']); ?>">
      <small>Shown across the top of the photo, e.g. SPOOKY NEWS, BREAKING BOOS, HAUNTED HEADLINES.</small>

      <label for="display_duration_seconds">Hold each name on screen for (seconds)</label>
      <input type="number" id="display_duration_seconds" name="display_duration_seconds" min="5" max="120"
             value="<?php echo tot_h($totSettings['display_duration_seconds']); ?>">

      <label class="tot-inline" style="display:block !important; margin-top:12px;">
        <input type="checkbox" name="local_show_photo" <?php echo $totSettings['local_show_photo'] ? 'checked' : ''; ?>>
        Use the photo zone for door-page entries (photo or costume art). Uncheck for ticker text only.
      </label>
      <label class="tot-inline" style="display:block !important; margin-top:8px;">
        <input type="checkbox" name="enabled" <?php echo $totSettings['enabled'] ? 'checked' : ''; ?>> Plugin enabled
      </label>
    </fieldset>

    <fieldset>
      <legend>Cloud service (optional)</legend>
      <p><small>Leave blank for a local-only show. If you have a submissions service that speaks the NaughtyNice Cloud
        <code>/api/v1</code> protocol (it may send <code>treat</code>/<code>trick</code>, or <code>nice</code>/<code>naughty</code>
        which map to treat/trick), enter it here and the daemon will poll it as well.</small></p>
      <label class="tot-inline" style="display:block !important;">
        <input type="checkbox" name="cloud_enabled" <?php echo $totSettings['cloud_enabled'] ? 'checked' : ''; ?>> Poll the cloud service when one is set
      </label>
      <p>
        <label class="tot-inline"><input type="radio" name="environment" value="prod" <?php echo $totSettings['environment'] === 'prod' ? 'checked' : ''; ?>> Production</label>
        &nbsp;&nbsp;
        <label class="tot-inline"><input type="radio" name="environment" value="dev" <?php echo $totSettings['environment'] === 'dev' ? 'checked' : ''; ?>> Development</label>
      </p>
      <?php foreach (TOT_ENVIRONMENT_NAMES as $envName): $env = $totSettings['environments'][$envName]; ?>
        <div style="border-left: 3px solid #ccc; padding-left: 10px; margin-top: 8px;">
          <strong><?php echo tot_h(ucfirst($envName)); ?></strong>
          <label for="<?php echo $envName; ?>_cloud_base_url">Service URL</label>
          <input type="text" id="<?php echo $envName; ?>_cloud_base_url" name="<?php echo $envName; ?>_cloud_base_url"
                 value="<?php echo tot_h($env['cloud_base_url']); ?>" placeholder="https://your-service.example.com">
          <label for="<?php echo $envName; ?>_token">Show token</label>
          <input type="password" id="<?php echo $envName; ?>_token" name="<?php echo $envName; ?>_token"
                 value="<?php echo tot_h($env['token']); ?>" autocomplete="off">
        </div>
      <?php endforeach; ?>
    </fieldset>

    <fieldset>
      <legend>Advanced</legend>
      <label for="fpp_base_url">Local FPP API URL</label>
      <input type="text" id="fpp_base_url" name="fpp_base_url" value="<?php echo tot_h($totSettings['fpp_base_url']); ?>">
      <small>Leave as http://localhost unless FPP is on a non-default port.</small>

      <label for="poll_interval_seconds">Cloud poll interval (seconds)</label>
      <input type="number" id="poll_interval_seconds" name="poll_interval_seconds" min="5" max="120" value="<?php echo tot_h($totSettings['poll_interval_seconds']); ?>">

      <label for="playlist">Playlist to trigger</label>
      <input type="text" id="playlist" name="playlist" value="<?php echo tot_h($totSettings['playlist']); ?>">

      <label for="ticker_model">Ticker overlay model name</label>
      <input type="text" id="ticker_model" name="ticker_model" value="<?php echo tot_h($totSettings['ticker_model']); ?>">

      <label for="matrix_width">Matrix width (px)</label>
      <input type="number" id="matrix_width" name="matrix_width" value="<?php echo tot_h($totSettings['matrix_width']); ?>">

      <label for="photo_zone_height">Photo zone height (px)</label>
      <input type="number" id="photo_zone_height" name="photo_zone_height" value="<?php echo tot_h($totSettings['photo_zone_height']); ?>">
      <small>Auto-managed by "Re-run zone setup" — only edit by hand if you're not using zone auto-setup.</small>
    </fieldset>

    <p><button type="submit">Save settings</button></p>
  </form>

  <?php if ($totCloud): ?>
  <form method="post" action="<?php echo $actionUrl; ?>">
    <input type="hidden" name="tot_action" value="test">
    <button type="submit">Test cloud connection (<?php echo tot_h(strtoupper($totActive['name'])); ?>)</button>
  </form>
  <?php endif; ?>
</div>
