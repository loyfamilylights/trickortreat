<?php
require_once __DIR__ . '/lib/config.php';
$totStatus = tot_read_status();
$totSettings = tot_load_settings();
$totQueue = tot_read_queue();
$totRunning = tot_daemon_running();
$totCloud = tot_cloud_configured($totSettings);
$last = isset($totStatus['last_item']) ? $totStatus['last_item'] : null;
?>
<style>
  .tot-status-table td { padding: 3px 12px 3px 0; }
  .tot-banner { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; margin: 0 6px 10px 0; color: #fff; }
  .tot-banner.run { background: #1a5d1a; } .tot-banner.stop { background: #8a1c1c; }
  .tot-banner.local { background: #5a1a8a; } .tot-banner.cloud { background: #b35c00; }
</style>
<div style="max-width: 560px;">
  <h3>&#127875; Trick-or-Treat News — Status</h3>
  <span class="tot-banner <?php echo $totRunning ? 'run' : 'stop'; ?>">DAEMON: <?php echo $totRunning ? 'RUNNING' : 'NOT RUNNING'; ?></span>
  <span class="tot-banner <?php echo $totCloud ? 'cloud' : 'local'; ?>"><?php echo $totCloud ? 'LOCAL + CLOUD' : 'LOCAL ONLY'; ?></span>
  <table class="tot-status-table">
    <tr><td>Enabled:</td><td><?php echo $totSettings['enabled'] ? 'yes' : 'no'; ?></td></tr>
    <tr><td>Waiting in line:</td><td><?php echo count($totQueue); ?></td></tr>
    <tr><td>Last shown:</td><td><?php echo $last ? tot_h($last['name'] . ' — ' . strtoupper($last['verdict']) . ' (' . $last['at'] . ')') : '—'; ?></td></tr>
    <tr><td>Shown (total):</td><td><?php echo tot_h($totStatus['items_processed_total'] ?? 0); ?></td></tr>
    <tr><td>Daemon heartbeat:</td><td><?php echo tot_h($totStatus['last_heartbeat_at'] ?? 'never'); ?></td></tr>
    <?php if ($totCloud): ?>
    <tr><td>Cloud license:</td><td><strong><?php echo tot_h($totStatus['license'] ?? 'unknown'); ?></strong></td></tr>
    <tr><td>Cloud last poll:</td><td><?php echo tot_h($totStatus['last_poll_at'] ?? 'never'); ?></td></tr>
    <?php endif; ?>
    <tr><td>Last error:</td><td><?php echo tot_h($totStatus['last_error'] ?? '—'); ?></td></tr>
    <tr><td>Plugin version:</td><td><?php echo tot_h($totStatus['plugin_version'] ?? '—'); ?></td></tr>
    <tr><td>FPP version:</td><td><?php echo tot_h($totStatus['fpp_version'] ?? '—'); ?></td></tr>
  </table>
  <p>
    <a href="plugin.php?plugin=<?php echo TOT_PLUGIN_NAME; ?>&page=content.php">Setup page</a> &nbsp;·&nbsp;
    <a href="<?php echo tot_h(tot_door_url()); ?>" target="_blank">Door page (phone)</a>
  </p>
</div>
