<?php
// Simple manual controller: use the *working Master FPP presets*, not a daemon.
// Only allows two hardcoded command names and sends requests to localhost.
$presets = ['treat' => 'treat', 'trick' => 'TRICK'];
$message = '';
$success = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $choice = (string)($_POST['verdict'] ?? '');
    if (!isset($presets[$choice])) {
        $message = 'Invalid selection.';
    } else {
        $url = 'http://127.0.0.1/api/command/Trigger%20Command%20Preset/' . rawurlencode($presets[$choice]);
        $response = false;
        $status = 0;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 6, CURLOPT_FOLLOWLOCATION => false]);
            $response = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 6, 'ignore_errors' => true]]);
            $response = @file_get_contents($url, false, $ctx);
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $status = (int)$m[1];
        }
        $success = $response !== false && $status >= 200 && $status < 300;
        $message = $success
            ? 'Sent ' . strtoupper($choice) . ' to the saved Master preset. Check Snow-Globe projector.'
            : 'FPP did not confirm the request (HTTP ' . $status . '). Check Master preset names and FPPD status.';
    }
}
?>
<style>
.tot-simple{max-width:680px;margin:24px auto;padding:18px;border:1px solid #555;border-radius:12px;font-family:system-ui,sans-serif;text-align:center}
.tot-simple .controls{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin:22px 0}
.tot-simple button{font-size:28px;font-weight:800;color:#fff;border:0;border-radius:12px;padding:26px 46px;cursor:pointer}
.tot-simple .treat{background:#bb5700}.tot-simple .trick{background:#6732aa}
.tot-simple .result{padding:12px;border-radius:8px;background:#ececec;color:#222}
.tot-simple small{display:block;line-height:1.6}
</style>
<section class="tot-simple">
<h2>🎃 Loy Family Lights — TRICK or TREAT</h2>
<p>Master <strong>192.168.1.192</strong> → Snow-Globe <strong>192.168.1.113</strong><br>224 × 126 HDMI VirtualMatrix</p>
<div class="controls">
<form method="post"><input type="hidden" name="verdict" value="treat"><button class="treat" type="submit">🍬 TREAT!</button></form>
<form method="post"><input type="hidden" name="verdict" value="trick"><button class="trick" type="submit">👻 TRICK!</button></form>
</div>
<?php if ($message !== ''): ?><p class="result" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p><?php endif; ?>
<small>Uses the existing Master FPP command presets named <code>treat</code> and <code>TRICK</code>. Both presets must already use the confirmed direct MultiSync Overlay Model Effect targeting Snow-Globe only.</small>
<small>No daemon, no new playlist, no channel changes. An HTTP success confirms the command request was accepted, not that the projector visibly updated.</small>
</section>
