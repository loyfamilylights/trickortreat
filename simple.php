<?php
// Local-only FPP API requests. No arbitrary user-supplied commands or destinations.
function tot_api_request($url, $method, $payload = null) {
    $result = ['ok' => false, 'code' => 0, 'body' => ''];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $payload;
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $result['code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result['ok'] = $body !== false;
        $result['body'] = $body === false ? curl_error($ch) : $body;
        curl_close($ch);
    } else {
        $http = ['method' => $method, 'timeout' => 8, 'ignore_errors' => true];
        if ($method === 'POST') { $http['header'] = "Content-Type: application/json\r\n"; $http['content'] = $payload; }
        $ctx = stream_context_create(['http' => $http]);
        $body = @file_get_contents($url, false, $ctx);
        $result['body'] = $body === false ? 'HTTP request failed' : $body;
        $result['ok'] = $body !== false;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $result['code'] = (int)$m[1];
    }
    return $result;
}
?>
<?php
// Simple manual controller: use the *working Master FPP presets*, not a daemon.
// Only allows two hardcoded command names and sends requests to localhost.
$presets = ['treat' => ['treat', 'TREAT', 'Treat'], 'trick' => ['TRICK', 'trick', 'Trick']];
$message = '';
$success = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $choice = (string)($_POST['verdict'] ?? '');
    if (!isset($presets[$choice])) {
        $message = 'Invalid selection.';
    } else {
        // Read the exact command already verified using Run Now on Master.
        // This is NOT the broken Trigger Command Preset route.
        $base = 'http://127.0.0.1';
        $remote = 'http://192.168.1.113';
        $preset = ['code' => 0, 'body' => ''];
        $definition = null;
        $name = '';
        foreach ($presets[$choice] as $candidate) {
            $test = tot_api_request($base . '/api/commandPresets/' . rawurlencode($candidate), 'GET');
            $parsed = json_decode($test['body'], true);
            if ($test['code'] === 200 && is_array($parsed)) {
                $preset = $test;
                $definition = $parsed;
                $name = $candidate;
                break;
            }
            $preset = $test;
        }
        if ($name === '') {
            $message = 'Could not read the saved ' . strtoupper($choice) . ' Master preset. Tried: ' . implode(', ', $presets[$choice]) . '. Last HTTP ' . $preset['code'] . '. Check its exact name in Master Command Presets.';
        } else {
            // Some FPP versions return the command definition wrapped in a single-element array.
            if (array_is_list($definition) && count($definition) === 1 && is_array($definition[0])) $definition = $definition[0];
            $cmd = (string)($definition['command'] ?? '');
            $hosts = $definition['multisyncHosts'] ?? '';
            $hostList = is_array($hosts) ? $hosts : preg_split('/[;,\s]+/', (string)$hosts, -1, PREG_SPLIT_NO_EMPTY);
            $ms = $definition['multisyncCommand'] ?? false;
            $enabled = $ms === true || $ms === 1 || $ms === '1' || $ms === 'true';
            $args = $definition['args'] ?? null;
            if ($cmd !== 'Overlay Model Effect' || !$enabled || !in_array('192.168.1.113', $hostList, true) || count($hostList) !== 1 || !is_array($args)) {
                $message = 'Safety check failed: Master ' . $name . ' must be a direct Overlay Model Effect with MultiSync targeting only Snow-Globe (192.168.1.113).';
            } else {
                $command = [
                    'command' => $cmd,
                    'multisyncCommand' => false,
                    'multisyncHosts' => '',
                    'args' => $args,
                ];
                $sent = tot_api_request($remote . '/api/command', 'POST', json_encode($command));
                $success = $sent['code'] >= 200 && $sent['code'] < 300 && $sent['ok'];
                $message = $success
                    ? 'Snow-Globe accepted ' . strtoupper($choice) . ' overlay request (HTTP ' . $sent['code'] . '). Check projector.'
                    : 'Snow-Globe direct command failed (HTTP ' . $sent['code'] . '): ' . substr(strip_tags($sent['body']), 0, 220);
            }
        }
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
<p><strong>CONTROL PANEL FIX v4 — Snow-Globe Direct API</strong></p>
<p>Master <strong>192.168.1.192</strong> → Snow-Globe <strong>192.168.1.113</strong><br>224 × 126 HDMI VirtualMatrix</p>
<div class="controls">
<form method="post"><input type="hidden" name="verdict" value="treat"><button class="treat" type="submit">🍬 TREAT!</button></form>
<form method="post"><input type="hidden" name="verdict" value="trick"><button class="trick" type="submit">👻 TRICK!</button></form>
</div>
<?php if ($message !== ''): ?><p class="result" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p><?php endif; ?>
<small>Reads and replays the direct Overlay Model Effect in the existing Master FPP command presets with names matching <code>treat</code> or <code>TRICK</code> (case-insensitive variants attempted). Both presets must already use direct MultiSync Overlay Model Effect targeting Snow-Globe only. The page reads each Master preset, then sends its overlay effect directly to Snow-Globe at 192.168.1.113, bypassing Master API MultiSync forwarding.</small>
<small>No daemon, new playlist, or channel changes. Run only on your trusted local network. An HTTP success means Snow-Globe accepted the request; inspect the projector to confirm actual display.</small>
</section>
